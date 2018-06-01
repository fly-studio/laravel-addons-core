<?php

namespace Addons\Core\Models;

use Cache;
use Addons\Core\Tools\TreeCollection;

trait TreeCacheTrait {

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

	public static function getTreeCache()
	{
		$model = new static();
		$hashKey = $model->getTable().'-all-data';

		return Cache::remember($hashKey, config('cache.ttl'), function() use ($model) {

			$builder = static::where($model->getKeyName(), '!=', 0);
			!empty($model->getOrderKeyName()) && $builder->orderBy($model->getOrderKeyName());

			$data = $builder->get()->keyBy($model->getKeyName())->toArray();

			$tree = static::datasetToTree($data);

			$collection = TreeCollection::make($tree);

			if (!empty($zero = static::find(0))) {
				$collection->root()->attributes($zero->toArray());
				$collection[0] = $collection->root();
			}

			return $collection;
		});

	}

}
