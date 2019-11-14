<?php

namespace Addons\Core;

use Schema, DB;
use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait ApiTrait {

	protected $apiOperators = [
		'in' => 'in', 'nin' => 'not in', 'is' => 'is',
		'min' => '>=', 'gte' => '>=', 'max' => '<=', 'lte' => '<=', 'btw' => 'between', 'nbtw' => 'not between', 'gt' => '>', 'lt' => '<',
		'neq' => '<>', 'ne' => '<>', 'eq' => '=', 'equal' => '=',
		'lk' => 'like', 'like' => 'like', 'lkb' => 'like binary',
		'nlk' => 'not like', 'nlkb' => 'not like binary',
		'rlk' => 'rlike', 'ilk' => 'ilike',
		'and' => '&', 'or' => '|', 'xor' => '^', 'left_shift' => '<<', 'right_shift' => '>>', 'bitwise_not' => '~', 'bitwise_not_any' => '~*', 'not_bitwise_not' => '!~', 'not_bitwise_not_any' => '!~*',
		'regexp' => 'regexp', 'not_regexp' => 'not regexp', 'similar_to' => 'similar to', 'not_similar_to' => 'not similar to',
	];

	public function _getColumns(Builder $builder)
	{
		static $table_columns;

		$query = $builder->getQuery();
		$tables = [$query->from];

		if (!empty($query->joins))
			foreach ($query->joins as $v)
				$tables[] = $v->table;

		$_columns = [];
		foreach ($tables as &$v)
		{
			list($table, $alias) = strpos(strtolower($v), ' as ') !== false ? explode(' as ', $v) : [$v, $v];

			if (!isset($table_columns[$table]))
				$table_columns[$table] = Schema::getColumnListing($table);
				//$table_columns[$table] = $query->getConnection()->getDoctrineSchemaManager()->listTableColumns($table);

			foreach ($table_columns[$table] as $key/* => $value*/)
				$_columns[$key] = isset($_columns[$key]) ? $_columns[$key] : $alias.'.'.$key;
		}
		return $_columns;
	}

	/**
	 * 给Builder绑定where条件
	 * 注意：参数的值为空字符串，则会忽略该条件
	 *
	 * @param  ArrayAccess $condition
	 * @param  Builder $builder
	 * @return array           返回筛选(搜索)的参数
	 */
	private function _doFilters(ArrayAccess $condition, Builder $builder, $columns = [])
	{
		$filters = $this->_getFilters($condition);

		foreach ($filters as $key => $filter)
		{
			$key = !empty($columns[$key]) ? $columns[$key] : $key;
			foreach ($filter as $method => $value)
			{
				if (empty($value) && !is_numeric($value)) continue; //''不做匹配

				$operator = $this->apiOperators[$method];

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
		return $filters;
	}

	private function _doQueries(ArrayAccess $condition, Builder $builder)
	{
		$queries = $this->_getQueries($condition);

		foreach ($queries as $key => $value)
			if ((!empty($value) || is_numeric($value)) && method_exists($builder->getModel(), 'scope'.ucfirst($key)))
				call_user_func_array([$builder, $key], Arr::wrap($value));

		return $queries;
	}

	private function _doOrders(ArrayAccess $condition, Builder $builder, $columns = [])
	{
		$orders = $this->_getOrders($condition, $builder);

		foreach ($orders as $k => $v)
			$builder->orderBy(isset($columns[$k]) ? $columns[$k] : $k, $v);

		return $orders;
	}
	/**
	 * 获取筛选(搜索)的参数
	 * &f[username][lk]=abc&f[gender][eq]=1
	 *
	 * @param  ArrayAccess $condition
	 * @param  Builder $builder
	 * @return array           返回参数列表
	 */
	public function _getFilters(ArrayAccess $condition)
	{
		$filters = [];
		$inputs = Arr::get($condition, 'f', []);
		if (!empty($inputs))
			foreach ($inputs as $k => $v)
				$filters[$k] = is_array($v) ? array_change_key_case($v) : ['eq' => $v];

		return $filters;
	}
	/**
	 * 获取全文搜索的参数
	 * &q[ofPinyin]=abc
	 *
	 * @param  ArrayAccess $condition
	 * @param  Builder $builder
	 * @return array           返回参数列表
	 */
	public function _getQueries(ArrayAccess $condition)
	{
		$inputs = Arr::get($condition, 'q', []);

		return empty($inputs) ? [] : $inputs;
	}
	/**
	 * 获取排序的参数
	 * 1. datatable 的方式
	 * 2. order[id]=desc&order[created_at]=asc 类似这种方式
	 * 默认是按主键倒序
	 *
	 * @param  ArrayAccess $condition
	 * @param  Builder $builder
	 * @return array           返回参数列表
	 */
	public function _getOrders(ArrayAccess $condition, Builder $builder)
	{
		$orders = Arr::get($condition, 'o', []);
		//默认按照主键的倒序
		return empty($orders) ? [$builder->getModel()->getKeyName() => 'desc'] : $orders;
	}

	public function _getPaginate(ArrayAccess $condition, Builder $builder, array $columns = ['*'], array $extra_query = [])
	{
		$size = Arr::get($condition, 'size') ?: config('size.models.'.$builder->getModel()->getTable(), config('size.common'));
		$page = Arr::get($condition, 'page', 1);

		if (Arr::get($condition, 'all') == 'true') $size = 10000;//$builder->count(); //为统一使用paginate输出数据格式,这里需要将size设置为整表数量

		$tables_columns = $this->_getColumns($builder);
		$filters = $this->_doFilters($condition, $builder, $tables_columns);
		$queries = $this->_doQueries($condition, $builder);
		$orders = $this->_doOrders($condition, $builder, $tables_columns);

		$paginate = $builder->paginate($size, $columns, 'page', $page);

		$query_strings = array_merge_recursive(['f' => $filters, 'q' => $queries], $extra_query);
		$paginate->appends($query_strings);

		$paginate->filters = $filters;
		$paginate->queries = $queries;
		$paginate->orders = $orders;
		return $paginate;
	}

	public function _getData(ArrayAccess $condition, Builder $builder, callable $callback = null, array $columns = ['*'])
	{
		$paginate = $this->_getPaginate($condition, $builder, $columns);

		if (is_callable($callback))
			call_user_func_array($callback, [$paginate]); //reference Objecy

		return $paginate->toArray() + ['filters' => $paginate->filters, 'queries' => $paginate->queries, 'orders' => $paginate->orders];
	}

	public function _getCount(ArrayAccess $condition, Builder $builder, $enable_filters = true)
	{
		$_b = clone $builder;

		if ($enable_filters)
		{
			$tables_columns = $this->_getColumns($builder);
			$this->_doFilters($condition, $_b, $tables_columns);
			$this->_doQueries($condition, $_b);
		}

		$query = $_b->getQuery();

		if (!empty($query->groups)) //group by
		{
			return $query->getCountForPagination($query->groups);
			// or
			$query->columns = $query->groups;
			return DB::table( DB::raw("({$_b->toSql()}) as sub") )
				->mergeBindings($_b->getQuery()) // you need to get underlying Query Builder
				->count();
		} else
			return $_b->count();
	}

	public function _getExport(ArrayAccess $condition, Builder $builder, callable $callback = null, array $columns = ['*'])
	{
		set_time_limit(600); //10min

		$size = Arr::get($condition, 'size') ?: config('size.export', 1000);

		$tables_columns = $this->_getColumns($builder);
		$this->_doFilters($condition, $builder, $tables_columns);
		$this->_doQueries($condition, $builder);

		$paginate = $builder->orderBy($builder->getModel()->getKeyName(),'DESC')->paginate($size, $columns);

		if (is_callable($callback))
			call_user_func_array($callback, [&$paginate]);

		$data = $paginate->toArray();

		!empty($data['data']) && Arr::isAssoc($data['data'][0]) && array_unshift($data['data'], array_keys($data['data'][0]));

		array_unshift($data['data'], [$builder->getModel()->getTable(), $data['from']. '-'. $data['to'].'/'. $data['total'], date('Y-m-d h:i:s')]);

		return $data['data'];
	}
}
