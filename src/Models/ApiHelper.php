<?php

namespace Addons\Core\Models;

use Addons\Core\Models\Builder as ModelsBuilder;
use Schema, DB;
use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiHelper {

    const defaultPerPage = 50;

    private Builder $builder;
    private int $page = 0;
    private int $perPage = 0;

    private array $queries = [];
    private array $filters = [];
    private array $orders = [];

    protected $apiOperators = [
        '0' => '>=', '1' => '<=',
        'in' => 'in', 'nin' => 'not in', 'is' => 'is',
        'min' => '>=', 'gte' => '>=', 'max' => '<=', 'lte' => '<=', 'btw' => 'between', 'nbtw' => 'not between', 'gt' => '>', 'lt' => '<',
        'neq' => '<>', 'ne' => '<>', 'eq' => '=', 'equal' => '=',
        'lk' => 'like', 'like' => 'like', 'lkb' => 'like binary',
        'nlk' => 'not like', 'nlkb' => 'not like binary',
        'rlk' => 'rlike', 'ilk' => 'ilike',
        'and' => '&', 'or' => '|', 'xor' => '^', 'left_shift' => '<<', 'right_shift' => '>>', 'bitwise_not' => '~', 'bitwise_not_any' => '~*', 'not_bitwise_not' => '!~', 'not_bitwise_not_any' => '!~*',
        'regexp' => 'regexp', 'not_regexp' => 'not regexp', 'similar_to' => 'similar to', 'not_similar_to' => 'not similar to',
    ];

    public static function make(Builder $builder, Request $request = null): static {
        return new static($builder, $request);
    }

    public function __construct(Builder $builder, Request $request = null) {
        if (is_null($request)) {
            $request = new Request();
        }
        $this->builder = $builder;
        $input = $this->getRequestInput($request);
        $this->filters($input['f'] ??  []);
        $this->orders($input['o'] ??  []);
        $this->queries($input['q'] ??  []);
        $this->page($request->input('page', 1));
        $this->perPage($request->input('size', static::defaultPerPage));

        if (boolval($request->input('all'))) {
            $this->perPage(PHP_INT_MAX);
        }
    }

    protected function getRequestInput(Request $request): array|null {
        $input = $request->input();

        $result = [];
        foreach($input as $key => $value) {
            switch(substr($key, 0, 3)) {
                case 'f':
                case 'o':
                case 'q':
                case 'f__':
                case 'o__':
                case 'q__':
                    $result[$key] = $value;
                    break;
            }
        }
        return $this->array_underscore($result);
    }

    protected function columns(string $tableName) {
        static $table_columns;

        if (!isset($table_columns[$tableName]))
            $table_columns[$tableName] = Schema::getColumnListing($tableName);
            //$table_columns[$table] = $query->getConnection()->getDoctrineSchemaManager()->listTableColumns($table);

        return $table_columns[$tableName];
    }

    protected function getColumnAlias()
    {
        $query = $this->builder->getQuery();
        $tables = [$query->from];

        if (!empty($query->joins))  {
            foreach ($query->joins as $v) {
                $tables[] = $v->table;
            }
        }

        $_columns = [];
        foreach ($tables as $name) {
            list($table, $alias) = strpos(strtolower($name), ' as ') !== false ? explode(' as ', $name) : [$name, $name];

            foreach ($this->columns($table) as $field)
                $_columns[$field] = isset($_columns[$field]) ? $_columns[$field] : $alias.'.'.$field;
        }
        return $_columns;
    }

    /**
     * 给Builder绑定where条件
     * 注意：参数的值为空字符串，则会忽略该条件
     *
     * @return array           返回筛选(搜索)的参数
     */
    private function _doFilters(Builder $builder, array $columnAlias = []): Builder
    {
        foreach ($this->filters as $key => $condition)
        {
            $key = !empty($columnAlias[$key]) ? $columnAlias[$key] : $key;
            foreach ($condition as $operator => $value)
            {
                if (empty($value) && !is_numeric($value) && !is_bool($value))
                    continue; //''不做匹配

                if (in_array($operator, ['like', 'like binary', 'not like', 'not like binary']))
                    $value = '%'.trim($value, '%').'%'; //添加开头结尾的*

                if ($operator == 'in')
                    $builder->whereIn($key, $value);
                else if ($operator == 'not in')
                    $builder->whereNotIn($key, $value);
                else
                    $builder->where($key, $operator ?: '=' , $value);
            }
        }

        return $builder;
    }

    private function _doQueries(Builder $builder): Builder
    {
        foreach ($this->queries as $key => $value) {
            if ((!empty($value) || is_numeric($value) || is_bool($value))
                && method_exists($builder->getModel(), 'scope'.ucfirst($key))) {
                call_user_func_array([$builder, $key], Arr::wrap($value));
            }
        }

        return $builder;
    }

    private function _doOrders(Builder $builder, $columnAlias = []): Builder
    {
        foreach ($this->orders as $key => $value) {
            $builder->orderBy(isset($columnAlias[$key]) ? $columnAlias[$key] : $key, $value);
        }

        return $builder;
    }

    public function page(int $page): static {
        $this->page = $page <= 0 ? 1 : $page;
        return $this;
    }

    public function perPage(int $perPage): static {
        $this->perPage = $perPage <=0 ? static::perPage : $perPage;
        return $this;
    }

    public function builder(Builder $builder): static {
        $this->builder = $builder;
        return $this;
    }

    public function filter(string $field, mixed $operator, mixed $value = null): static {
        if (!isset($this->filters[$field]))
            $this->filters[$field] = [];

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtolower($operator);
        $operator = $this->apiOperators[$operator] ?? $operator;

        $this->filters[$field][$operator] = $value;
        return $this;
    }

    public function query(string $field, mixed $value = null): static {
        $this->queries[$field] = $value;
        return $this;
    }

    public function order(string $field, string $ascDesc = 'asc'): static {
        $this->orders[$field] = $ascDesc;
        return $this;
    }

    private function array_underscore(array $from): array {
        $result = [];

        foreach($from as $key => $value) {
            $keys = explode('__', $key);
            $array = &$result;
            foreach ($keys as $i => $key) {
                if (count($keys) === 1) {
                    break;
                }
                unset($keys[$i]);

                // If the key doesn't exist at this depth, we will just create an empty array
                // to hold the next value, allowing us to create the arrays to hold final
                // values at the correct depth. Then we'll keep digging into the array.
                if (! isset($array[$key]) || ! is_array($array[$key])) {
                    $array[$key] = [];
                }

                $array = &$array[$key];
            }

            $array[array_shift($keys)] = $value;
        }

        return $result;
    }

    /**
     * 设置筛选(搜索)的参数
     * URL中参数示例：&f[username][lk]=abc&f[gender][eq]=1
     * @param array|null $filters
     * @example under score key ['name__like' => 'abc']
     * @example nested key ['name' => ['like' => 'abc']]
     */
    public function filters(?array $filters): static {
        $this->filters = [];
        if (empty($filters))
            return $this;

        $result = $this->array_underscore($filters);
        foreach ($result as $field => $condition) {
            $_condition = is_array($condition) ? $condition : ['=' => $condition];
            foreach ($_condition as $operator => $value) {
                $this->filter($field, $operator, $value);
            }
        }

        return $this;
    }

    /**
     * 设置排序的参数
     * 1. datatable 的方式
     * 2. URL中参数示例：&o[id]=desc&o[created_at]=asc 类似这种方式
     * 默认是按主键倒序
     */
    public function orders(?array $orders): static {
        $this->orders = !empty($orders) ? $orders : [$this->builder->getModel()->getKeyName() => 'desc'];
        return $this;
    }

    /**
     * 获取全文搜索的参数
     * URL中参数示例：&q[ofPinyin]=abc
     */
    public function queries(?array $queries): static {
        $this->queries = !empty($queries) ? $queries : [];
        return $this;
    }

    public function count(bool $withFilters = false): int {

        $_b = clone $this->builder;

        if ($withFilters)
        {
            $columnAlias = $this->getColumnAlias();
            $this->_doFilters($_b, $columnAlias);
            $this->_doQueries($_b);
        }

        $query = $_b->getQuery();

        if (!empty($query->groups)) //group by
        {
            return $query->getCountForPagination($query->groups);
            // or
            // $query->columns = $query->groups;
            // return DB::table( DB::raw("({$_b->toSql()}) as sub") )
            //     ->mergeBindings($_b->getQuery()) // you need to get underlying Query Builder
            //     ->count();
        } else
            return $_b->count();
    }

    public function paginate(array $columns = ['*']): LengthAwarePaginator
    {
        $_b = clone $this->builder;

        $columnAlias = $this->getColumnAlias($_b);
        $filters = $this->_doFilters($_b, $columnAlias);
        $queries = $this->_doQueries($_b);
        $orders = $this->_doOrders($_b, $columnAlias);

        $paginate = $_b->paginate($this->perPage, $columns, 'page', $this->page);

        $paginate->filters = $filters;
        $paginate->queries = $queries;
        $paginate->orders = $orders;
        return $paginate;
    }

    public function data(callable $callback = null, array $columns = ['*']): array|null {

        $paginate = $this->paginate($columns);

        if (is_callable($callback))
            call_user_func_array($callback, [$paginate]); // reference Objecy

        return $paginate->toArray() + ['filters' => $this->filters, 'queries' => $this->queries, 'orders' => $this->orders];
    }

    public function export(callable $callback = null, array $columns = ['*']): array|null {

        set_time_limit(600); // 10 min

        $_b = clone $this->builder;

        $columnAlias = $this->getColumnAlias();
        $this->_doFilters($_b, $columnAlias);
        $this->_doQueries($_b);
        $this->_doOrders($_b);

        $paginate = $_b->paginate($this->perPage, $columns);

        if (is_callable($callback))
            call_user_func_array($callback, [$paginate]);

        $data = $paginate->toArray();

        if (!empty($data['data']) && Arr::isAssoc($data['data'][0]))
            array_unshift($data['data'], array_keys($data['data'][0]));

        array_unshift($data['data'], [$_b->getModel()->getTable(), $data['from']. '-'. $data['to'].'/'. $data['total'], date('Y-m-d h:i:s')]);

        return $data['data'];
    }

    public function datable(callable $callback = null, array $columns = ['*']): array|null {
        $total = $this->count();
        $data = $this->data($callback, $columns);
        $data['recordsTotal'] = $total; //不带 f q 条件的总数
        $data['recordsFiltered'] = $data['filter']; //带 f q 条件的总数
        return $data;
    }

}