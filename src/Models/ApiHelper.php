<?php

namespace Addons\Core\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 这是一个用于Request QueryString的Model搜索并输出的工具，并不是为了替代Model::where()、get()、find()、findMany()
 * 比如：?f__name=Alice&o__created_at=desc&q__status=active
 * 关于空字符串
 * 由于Form构造GET请求时，QueryString中会有空字符串的情况出现，比如：?f__name=&f__gender=male，这种情况表示f__name无效。
 * 所以filters(['name'=>''])时，会忽略name的条件。
 * 如果的确需要启用空字符串、或null，可以使用where('name', '')，where('name', 'is', null);
 * query同理
 *
 */
class ApiHelper {

    const defaultPerPage = 50;
    private const F_EMPTY_STRING = '__F_EMPTY_STRING__';
    private const F_NULL = '__F_NULL__';

    protected Builder $builder;
    protected int $page = 0;
    protected int $perPage = 0;

    protected array $columnsCache = [];

    protected array $queries = [];
    protected array $filters = [];
    protected array $orders = [];

    protected array $defaultOrders = [];

    protected static $operatorsTable = [
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
        $this->queries($input['q'] ??  []);
        $this->orders($input['o'] ??  []);
        $this->page($request->input('page', 1));
        $this->perPage($request->input('size', $request->input('per_page', static::defaultPerPage)));

        $this->defaultOrders = [$this->builder->getModel()->getKeyName() => 'desc'];
    }

    /**
     * 获取和api helper相关的request参数
     */
    protected function getRequestInput(Request $request): ?array {
        $input = $request->input();

        $result = [];
        foreach($input as $key => $value) {
            if (in_array(substr($key, 0, 3), ['f', 'o', 'q', 'f__', 'o__', 'q__'])) {
                $result[$key] = $value;
            }
        }
        return $this->array_underscore($result);
    }

    /**
     * 获取某table的所有字段列表，如果输入别名$alias，则返回字段名会变成：$alias.$column
     */
    protected function getColumns(string $tableName, string $alias = null) {
        static $table_columns;

        if (!isset($table_columns[$tableName]))
            $table_columns[$tableName] = Schema::getColumnListing($tableName);
            //$table_columns[$table] = $query->getConnection()->getDoctrineSchemaManager()->listTableColumns($table);

        return !empty($alias)
            ? array_map(fn($column) => $alias .'.'.$column, $table_columns[$tableName])
            : $table_columns[$tableName];
    }

    /**
     * 获取当前builder的table名列表：['table1', 'table2', ...]
     */
    protected function getTables() {
        $query = $this->builder->getQuery();
        $tables = [$query->from];

        if (!empty($query->joins))  {
            foreach ($query->joins as $v) {
                $tables[] = $v->table;
            }
        }

        return $tables;
    }

    /**
     * 获取当前builder的tables列表
     * [alias => table]
     */
    protected function getTableAlias() {
        $tables = [];
        foreach ($this->getTables() as $name) {
            [$table, $alias] = strpos(strtolower($name), ' as ') !== false ? explode(' as ', $name) : [$name, $name];
            $tables[$alias] = $table;
        }

        return $tables;
    }

    /**
     * 获取完整的字段列表，如果遇到相同字段，以前面的表的字段为准
     * [column => alias.column]
     */
    protected function getColumnAlias(): array {
        // 一个builder 只需要读取一次
        if (!empty($this->columnsCache)) {
            return $this->columnsCache;
        }

        $columns = [];
        $tables = $this->getTableAlias();
        foreach($tables as $alias => $table) {
            foreach ($this->getColumns($table) as $column) {
                // 如果遇到相同字段以前面的表的字段为准
                $columns[$column] = isset($columns[$column]) ? $columns[$column] : $alias.'.'.$column;
            }
        }

        return $this->columnsCache = $columns;
    }

    /**
     * 将一个columns数组转为正常的
     */
    public function columnsToFull(array $columns): array {
        $tableAlias = $this->getTableAlias(); // [alias => table]
        $columnAlias = $this->getColumnAlias(); // [field => alias.column]

        $realColumns = [];
        foreach($columns as $column) {
            if ($column == '*') { // 是 *，加入所有tables的字段
                foreach($tableAlias as $alias => $table) {
                    $realColumns = array_merge($realColumns, $this->getColumns($table, $alias));
                }
            } else if (strpos($column, '.*') !== false) { // 是table.*（没有严格校验），加入该table的所有字段
                [$alias, ] = explode('.', $column, 2);
                $realColumns = array_merge($realColumns, $this->getColumns($tableAlias[$alias] ?? $alias, $alias));
            } else {
                $realColumns[] = $columnAlias[$column] ?? $column;
            }
        }

        return $realColumns;
    }


    /**
     * 给Builder绑定where条件
     *
     * @return static
     */
    private function _doFilters(Builder $builder): static {
        $columnAlias = $this->getColumnAlias();
        foreach ($this->filters as $field => $condition)
        {
            $field = $columnAlias[$field] ?? $field;
            foreach ($condition as $operator => $value)
            {
                if (empty($value) && !is_numeric($value) && !is_bool($value))
                    continue; // ''不做匹配

                if ($value == static::F_EMPTY_STRING)
                    $value = '';
                else if ($value == static::F_NULL)
                    $value = null;

                if (in_array($operator, ['like', 'like binary', 'not like', 'not like binary'])) {
                    if (strpos($value, '%') === false) {
                        $value = '%'.$value.'%';
                    }
                }

                if ($operator == 'in')
                    $builder->whereIn($field, $value);
                else if ($operator == 'not in')
                    $builder->whereNotIn($field, $value);
                else
                    $builder->where($field, $operator ?: '=' , $value);
            }
        }

        return $this;
    }


    /**
     * 给Builder绑定scopeXXX的条件
     * 注意：参数的值为空字符串，会忽略该条件
     *
     * @return Builder
     */
    private function _doQueries(Builder $builder): static {
        foreach ($this->queries as $key => $value) {
            if ((!empty($value) || is_numeric($value) || is_bool($value))
                && method_exists($builder->getModel(), 'scope'.ucfirst($key))) {

                if ($value == static::F_EMPTY_STRING)
                    $value = '';
                else if ($value == static::F_NULL)
                    $value = null;

                call_user_func_array([$builder, $key], Arr::wrap($value));
            }
        }

        return $this;
    }

    /**
     * 给Builder绑定orderBy的条件
     *
     * @return static
     */
    private function _doOrders(Builder $builder): static {
        $columnAlias = $this->getColumnAlias();
        $orders = $this->orders ?: $this->defaultOrders;
        foreach ($orders as $key => $value) {
            $builder->orderBy($columnAlias[$key] ?? $key, $value);
        }

        return $this;
    }

    /**
     * 给Builder绑定offset、limit的条件
     */
    private function _doPage(Builder $builder, int $page = null, int $perPage = null): static {
        $page = $page ?? $this->page;
        $perPage = $perPage ?? $this->perPage;

        $builder->offset(($page - 1) * $perPage);
        $builder->limit($perPage);

        return $this;
    }

    /**
     * 设置页码，注意页码是从1开始
     */
    public function page(int $page): static {
        $this->page = $page <= 0 ? 1 : $page;
        return $this;
    }

    /**
     * 设置每页的数量
     */
    public function perPage(int $perPage): static {
        $this->perPage = $perPage <=0 ? static::defaultPerPage : $perPage;
        return $this;
    }

    /**
     * 设置Builder
     */
    public function builder(Builder $builder): static {
        $this->builder = $builder;
        $this->columnsCache = []; // 清理当前的columnsCache
        return $this;
    }

    /**
     * 设置一个筛选条件，$operator支持Model的where的操作符。
     * 别名$operator参考顶部的operatorsTable
     *
     * @param string $field 字段名
     * @param string|mixed $operator 条件，比如：like、lk、eq、=、gte、>=
     * @param mixed $value 条件需要的值
     * @param bool $strict 严格模式下，value为'' 或 null，会在执行是跳过
     *
     * @example / 如果只输入2个参数，表示$operator是=，比如：where('name', 'Alice');
     * @example / 如果$operator为like，并且$value中没有%，会自动添加百分号在前后：%Alice%，比如：where('name', 'like', 'Alice');
     * @example / 支持null：where('name', 'is', null)、where('name', 'is not', null)
     * @example / 支持空字符串：where('name', '')、where('name', '>=', '')
     */
    public function where(string $field, mixed $operator, mixed $value = null, bool $strict = false): static {
        if (!isset($this->filters[$field]))
            $this->filters[$field] = [];

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtolower($operator);
        $operator = static::$operatorsTable[$operator] ?? $operator;

        if (!$strict) {
            if ($value === '') {
                $value = static::F_EMPTY_STRING;
            } else if (is_null($value)) {
                $value = static::F_NULL;
            }
        }

        $this->filters[$field][$operator] = $value;
        return $this;
    }

    public function whereNull(string $field): static {
        return $this->where($field, 'is', null);
    }

    public function whereNotNull(string $field): static {
        return $this->where($field, 'is not', null);
    }

    /**
     * 设置一个scopeXX的筛选条件
     *
     * @param string $field 字段名
     * @param mixed value 调用scopeXX时的参数，可以为1项，或者数组
     * @param bool $strict 严格模式下，value为'' 或 null，会在执行是跳过
     *
     * @example / query('ofStatus', 'active') 将调用Model中的：scopeOfStatus($builder, 'active')
     * @example / query('ofStatus', ['active', 'inactive']) 将调用Model中：scopeOfStatus($builder, 'active', 'inactive')
     */
    public function query(string $field, mixed $value = null, bool $strict = false): static {
        if (!$strict) {
            if ($value === '') {
                $value = static::F_EMPTY_STRING;
            } else if (is_null($value)) {
                $value = static::F_NULL;
            }
        }

        $this->queries[$field] = $value;
        return $this;
    }

    /**
     * 设置一个order by
     *
     * @param string $field 字段名
     * @param string $ascDesc asc 或 desc，其它会强转为asc
     *
     * @example / orderBy('name', 'desc')
     */
    public function orderBy(string $field, ?string $ascDesc = 'asc'): static {
        $ascDesc = strtolower($ascDesc ?? '');
        $this->orders[$field] = in_array($ascDesc, ['asc', 'desc']) ? $ascDesc : 'asc';
        return $this;
    }

    /**
     * 通过__来切割1维数组为多维数组，注意：不会递归切割，只切割1维
     * 注意：由于.在数据库中是表名.字段的连接符，所以必须使用__为分隔符。参考的Django
     */
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
     *
     * @param array|null $filters 支持2种格式
     *
     * @example / 中括号： ?f[username][lk]=abc&f[gender][eq]=1
     * @example / 下划线： ?f__username__lk=abc&f__gender__eq=1 可以混合使用
     * @example / under score key ['name__like' => 'abc']
     * @example / nested key ['name' => ['like' => 'abc']]
     */
    public function filters(?array $filters): static {
        $this->filters = [];
        if (empty($filters))
            return $this;

        $_filters = $this->array_underscore($filters);
        foreach ($_filters as $field => $condition) {
            $_condition = is_array($condition) ? $condition : ['=' => $condition];
            foreach ($_condition as $operator => $value) {
                $this->where($field, $operator, $value, strict:true);
            }
        }

        return $this;
    }

    /**
     * 设置排序的参数
     * @example URL中参数示例：?o[id]=desc&o[created_at]=asc，或?o__id=desc&o__created_at=asc 可以混合使用
     */
    public function orders(?array $orders): static {
        $this->orders = [];
        if (empty($orders))
            return $this;

        foreach($orders ?? [] as $field => $ascDesc) {
            $this->orderBy($field, $ascDesc);
        }
        return $this;
    }

    /**
     * 设置1个orders都没设置时的默认排序
     * 当类构造时，会将$this->defaultOrders设置为id倒序
     */
    public function defaultOrders(array $defaultOrders): static {
        $this->defaultOrders = $defaultOrders;
        return $this;
    }

    /**
     * 获取基于scopeXX的搜索的参数，比如一些组合条件（title like '' or content like ''）可以用这个来实现
     *
     * @example / 中括号示例1：?q[ofStatus]=active，将调用Model中：scopeOfStatus($builder, 'active')
     * @example / 下划线示例2：?q__ofStatus=active，将调用Model中：scopeOfStatus($builder, 'active')
     * @example / 多参数示例3：?q__ofStatus[]=active&q__ofStatus[]=inactive，将调用Model中：scopeOfStatus($builder, 'active', 'inactive')
     */
    public function queries(?array $queries): static {
        $this->queries = [];
        if (empty($queries))
            return $this;

        foreach($queries ?? [] as $field => $value) {
            $this->query($field, $value, strict:true);
        }
        return $this;
    }

    public function count(bool $withFilters = false): int {

        $_b = clone $this->builder;

        if ($withFilters)
        {
            $this->_doFilters($_b);
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

    public function getSelectColumns(array|string $columns, array $excludeColumns): array {
        if (!empty($excludeColumns)) {
            $columns = $this->columnsToFull(Arr::wrap($columns));
            $excludeColumns = $this->columnsToFull($excludeColumns);

            return array_diff($columns, $excludeColumns);
        } else {
            return $columns;
        }
    }

    /**
     * 类似于Model::find($id)，额外有excludeColumns参数，可以在SELECT侧过滤字符
     * 按条件、以及指定ID的获取Model数据
     * 会启用filters等条件(原Model也可以在find前设置条件)
     *
     * 类似于Model::where(filters...)
     *  ->scopeXX(queries...)
     *  ->limit($this->perPage)
     *  ->find($id);
     */
    public function find(mixed $id, array|string $columns = ['*'], array $excludeColumns = []): Model {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->find($id, $columns);
    }

    /**
     * 类似于Model::findMany($ids)，额外有excludeColumns参数，可以在SELECT侧过滤字符
     * 按条件、以及指定IDs的获取Model集合数据
     * 会启用filters等条件、以及page，perPage(原Model也可以在findMany前设置条件)
     *
     * 类似于Model::where(filters...)
     *  ->scopeXX(queries...)
     *  ->orderBy(orders...)
     *  ->offset((page - 1) * perPage)
     *  ->limit($this->perPage)
     *  ->findMany($ids);
     */
    public function findMany(Arrayable|array $ids, array|string $columns = ['*'], array $excludeColumns = []): Collection {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b)
            ->_doOrders($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->findMany($ids, $columns);
    }

    /**
     * 按条件获取第一条Model数据，会启用page，perPage，即offset=(page-1)*perPage
     * 类似于Model::where(filters...)
     *  ->scopeXX(queries...)
     *  ->orderBy(orders...)
     *  ->offset((page - 1) * perPage)
     *  ->limit(1)
     *  ->get()->first();
     */
    public function first(array|string $columns = ['*'], array $excludeColumns = []): Model | null {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b)
            ->_doOrders($_b)
            ->_doPage($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->first($columns);
    }

    /**
     * 按条件获取Model集合数据，会启用page，perPage
     * 类似于Model::where(filters...)
     *  ->scopeXX(queries...)
     *  ->orderBy(orders...)
     *  ->offset((page - 1) * perPage)
     *  ->limit(perPage)
     *  ->get()
     */
    public function get(array|string $columns = ['*'], array $excludeColumns = []): Collection {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b)
            ->_doOrders($_b)
            ->_doPage($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->get($columns);
    }

    /**
     * 按条件获取Model集合的全量数据，不会启用page，perPage
     * 类似于Model::where(filters...)
     *  ->scopeXX(queries...)
     *  ->orderBy(orders...)
     *  ->get();
     */
    public function all(array|string $columns = ['*'], array $excludeColumns = []): Collection {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b)
            ->_doOrders($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->get($columns);
    }


    /**
     * 按条件获取分页的对象（LengthAwarePaginator）
     * @return LengthAwarePaginator
     */
    public function paginate(array|string $columns = ['*'], array $excludeColumns = []): LengthAwarePaginator {
        $_b = clone $this->builder;

        $this->_doFilters($_b)
            ->_doQueries($_b)
            ->_doOrders($_b);

        $columns = $this->getSelectColumns($columns, $excludeColumns);
        return $_b->paginate($this->perPage, $columns, 'page', $this->page);
    }

    /**
     * 按条件获取分页的数据（array），包含：分页、数据、设置的各项条件、排序
     * @return array
     */
    public function data(callable $callback = null, array|string $columns = ['*'], array $excludeColumns = []): array {
        $paginate = $this->paginate($columns, $excludeColumns);

        if (is_callable($callback))
            $paginate = call_user_func_array($callback, [$paginate]) ?? $paginate; // reference Objecy

        return $paginate->toArray() + ['filters' => $this->filters, 'queries' => $this->queries, 'orders' => $this->orders];
    }

    /**
     * 按条件获取支持datable使用的分页数据（array）
     */
    public function datable(callable $callback = null, array $columns = ['*'], array $excludeColumns = []): array {
        // 不带 f q 条件的总数
        $total = $this->count();
        $data = $this->data($callback, $columns, $excludeColumns);

        $data['recordsTotal'] = $total; // 不带 f q 条件的总数
        $data['recordsFiltered'] = $data['total']; // 带 f q 条件的总数
        return $data;
    }

}