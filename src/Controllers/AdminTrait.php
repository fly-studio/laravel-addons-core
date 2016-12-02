<?php
namespace Addons\Core\Controllers;

use Closure, Schema, DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
trait AdminTrait {

	private function _getColumns(Builder $builder)
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
	 * @param  Request $request 
	 * @param  Builder $builder 
	 * @return array           返回筛选(搜索)的参数
	 */
	private function _doFilter(Request $request, Builder $builder, $columns = [])
	{
		$filters = $this->_getFilters($request);
		$operators = [
			'in' => 'in', 'not_in' => 'not in', 'is' => 'is', 'min' => '>=', 'greater_equal' => '>=', 'max' => '<=', 'less_equal' => '<=', 'between' => 'between', 'not_between' => 'not between', 'greater' => '>', 'less' => '<', 'not_equal' => '<>', 'inequal' => '<>', 'equal' => '=',
			'like' => 'like', 'left_like' => 'like', 'right_like' => 'like', 'rlike' => 'rlike', 'ilike' => 'ilike', 'like_binary' => 'like binary', 'left_like_binary' => 'like binary', 'right_like_binary' => 'like binary', 'not_like' => 'not like', 'not_left_like' => 'not like', 'not_right_like' => 'not like',
			'and' => '&', 'or' => '|', 'xor' => '^', 'left_shift' => '<<', 'right_shift' => '>>', 'bitwise_not' => '~', 'bitwise_not_any' => '~*', 'not_bitwise_not' => '!~', 'not_bitwise_not_any' => '!~*',
			'regexp' => 'regexp', 'not_regexp' => 'not regexp', 'similar_to' => 'similar to', 'not_similar_to' => 'not similar to',
		];

		array_walk($filters, function($v, $key) use ($builder, $operators, $columns) {
			$key = !empty($columns[$key]) ? $columns[$key] : $key;
			array_walk($v, function($value, $method) use ($builder, $key, $operators){
				if (empty($value) && $value !== '0') return; //''不做匹配
				else if (in_array($method, ['like', 'like_binary', 'not_like'])) $value = '%'.$value.'%';
				else if (in_array($method, ['left_like', 'left_like_binary', 'not_left_like'])) $value = $value.'%';
				else if (in_array($method, ['right_like', 'right_like_binary', 'not_right_like'])) $value = '%'.$value;
				if ($operators[$method] == 'in')
					$builder->whereIn($key, $value);
				else if ($operators[$method] == 'not in')
					$builder->whereNotIn($key, $value);
				else
					$builder->where($key, $operators[$method] ?: '=' , $value);
			});
		});
		return $filters;
	}

	private function _doOrder(Request $request, Builder $builder, $columns = [])
	{
		$orders = $this->_getOrders($request, $builder);
		foreach ($orders as $k => $v)
			$builder->orderBy($columns[$k] ?: $k, $v);
		return $orders;
	}
	/**
	 * 获取筛选(搜索)的参数
	 * &filters[username][like]=abc&filters[gender][equal]=1
	 * 
	 * @param  Request $request 
	 * @param  Builder $builder 
	 * @return array           返回参数列表
	 */
	private function _getFilters(Request $request)
	{
		$filters = [];
		$inputs = $request->input('filters') ?: [];
		foreach ($inputs as $k => $v)
			$filters[$k] = is_array($v) ? array_change_key_case($v) : ['equal' => $v];

		return $filters;
	}
	/**
	 * 获取排序的参数
	 * 1. datatable 的方式
	 * 2. order[id]=desc&order[created_at]=asc 类似这种方式
	 * 默认是按主键倒序
	 *
	 * @param  Request $request 
	 * @param  Builder $builder 
	 * @return array           返回参数列表
	 */
	private function _getOrders(Request $request, Builder $builder)
	{
		$columns = $request->input('columns') ?: [];
		$inputs = $request->input('order') ?: [];

		$orders = [];
		if(!empty($columns))
			foreach ($inputs as $v)
				!empty($columns[$v['column']]['data']) && $orders[$columns[$v['column']]['data']] = strtolower($v['dir']); 
		else
			$orders = $inputs;
		//默认按照主键的倒序
		empty($orders) && $orders = [$builder->getModel()->getKeyName() => 'desc'];
		return $orders;
	}

	private function _getPaginate(Request $request, Builder $builder, array $columns = ['*'], array $extra_query = [])
	{
		$pagesize = $request->input('pagesize') ?: ($request->input('length') ?: config('site.pagesize.admin.'.$builder->getModel()->getTable(), $this->site['pagesize']['common']));
		$page = $request->input('page') ?: (floor(($request->input('start') ?: 0) / $pagesize) + 1);
		if ($request->input('all') == 'true') $pagesize = 10000;//$builder->count(); //为统一使用paginate输出数据格式,这里需要将pagesize设置为整表数量

		$tables_columns = $this->_getColumns($builder);
		$filters = $this->_doFilter($request, $builder, $tables_columns);
		$orders = $this->_doOrder($request, $builder, $tables_columns);

		$paginate = $builder->paginate($pagesize, $columns, 'page', $page);

		$queries = array_merge_recursive(compact('filters'), $extra_query);
		foreach ($queries as $k => $v)
			$paginate->addQuery($k, $v);

		$paginate->filters = $filters;
		$paginate->orders = $orders;
		return $paginate;
	}

	private function _getData(Request $request, Builder $builder, Closure $callback = NULL, array $columns = ['*'])
	{
		$paginate = $this->_getPaginate($request, $builder, $columns);

		if (!empty($callback) && is_callable($callback))
			call_user_func_array($callback, [$paginate]); //reference Objecy

		return $paginate->toArray() + ['filters' => $paginate->filters, 'orders' => $paginate->orders];
	}

	private function _getCount(Request $request, Builder $builder, $enable_filters = TRUE)
	{
		$_b = clone $builder;
		if ($enable_filters)
		{
			$tables_columns = $this->_getColumns($builder);
			$this->_doFilter($request, $_b, $tables_columns);
		}
		$query = $_b->getQuery();
		if (!empty($query->groups)) //group by
		{
			return DB::table( DB::raw("({$_b->toSql()}) as sub") )
			->mergeBindings($_b->getQuery()) // you need to get underlying Query Builder
			->count();
		} else
			return $_b->count();
	}

	private function _getExport(Request $request, Builder $builder, Closure $callback = NULL, array $columns = ['*']) {
		set_time_limit(600); //10min

		$pagesize = $request->input('pagesize') ?: config('site.pagesize.export', 1000);
		$tables_columns = $this->_getColumns($builder);
		$this->_doFilter($request, $builder, $tables_columns);
		$paginate = $builder->orderBy($builder->getModel()->getKeyName(),'DESC')->paginate($pagesize, $columns);
		if (!empty($callback) && is_callable($callback))
			call_user_func_array($callback, [&$paginate]);
		$data = $paginate->toArray();
		!empty($data['data']) && is_assoc($data['data'][0]) && array_unshift($data['data'], array_keys($data['data'][0]));
		array_unshift($data['data'], [$builder->getModel()->getTable(), $data['from']. '-'. $data['to'].'/'. $data['total'], date('Y-m-d h:i:s')]);
		return $data['data'];
	}
}