<?php
namespace Addons\Core\Models;
use Cache;
trait TreeCacheTrait{
	public static $cacheTree;

	protected static function bootTreeCacheTrait()
	{
		/*		
		static::created(function($model) {
			Cache::forget($model->getTable().'-all-data')

		});
		static::updated(function($model) {
			Cache::forget($model->getTable().'-all-data')
		});
		*/
		static::deleted(function($model) {
			Cache::forget($model->getTable().'-all-data');
		});
		static::saved(function($model) {
			Cache::forget($model->getTable().'-all-data');
		});
		if (method_exists(static::class, 'restored'))
			static::restored(function($model){
				Cache::forget($model->getTable().'-all-data');
			});
	}

	public static function getAll($name_field = NULL)
	{
		$model = new static;
		$hashKey = $model->getTable().'-all-data';
		empty(static::$cacheTree) && static::$cacheTree = Cache::remember($hashKey, config('cache.ttl'), function() use ($model) {
			$builder = static::where($model->getKeyName(), '!=', 0);
			!empty($model->getOrderKeyName()) && $builder->orderBy($model->getOrderKeyName());
			$data = $builder->get()->keyBy($model->getKeyName())->toArray();
			foreach ($data as $item)
				$data[ ($item[$model->getParentKeyName()]) ][ 'children' ][ ($item[$model->getKeyName()]) ] = &$data[ ($item[$model->getKeyName()]) ];
			//得到一颗以id为key树
			return ['id' => $data];
		});
		
		if (!empty($name_field) && !isset(static::$cacheTree[$name_field]))
		{
			$root = static::$cacheTree['id'][ 0 ][ 'children' ];
			//将树的key变为name
			$data_with_name = [];
			$method = function(&$from_data, &$todata) use(&$method, $name_field) {
				foreach($from_data as &$value)
				{
					$todata[$value[$name_field]] = $value;
					unset($todata[$value[$name_field]]['children']);
					!empty($value['children']) && $method($value['children'], $todata[$value[$name_field]]['children']);
				}
			};
			$method($root, $data_with_name);
			static::$cacheTree[$name_field] = $data_with_name;
			Cache::put($hashKey, static::$cacheTree, config('cache.ttl'));
		}
			
		return static::$cacheTree;
	}

}