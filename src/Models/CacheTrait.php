<?php

namespace Addons\Core\Models;

use Cache;
use Illuminate\Support\Arr;

trait CacheTrait {

	/*
	 * forget these keys when fire
	 *
	 * @var array
	 */
	//protected $fire_caches = [];

	protected static function bootCacheTrait()
	{
		static::deleted(function($model) {
			$model->deleteFireCache();
		});
		static::saved(function($model) {
			$model->deleteFireCache();
		});
		if (method_exists(static::class, 'restored'))
			static::restored(function($model){
				$model->deleteFireCache();
		});
	}

	private static function getCacheIndex($id)
	{
		return floor($id / 100);
	}

	private static function getRangeCache($index)
	{
		$model = new static();
		$keyName = $model->getKeyName();
		$min = $index;
		$max = $min + 1;
		$key = $model->getTable().','.$min.'-'.$max;
		return Cache::remember($key, config('cache.ttl'), function() use($min, $max, $keyName){ // 1 days
			$models = static::where($keyName, '>=', $min * 100)->where($keyName, '<', $max * 100)->get();
			return $models->mapWithKeys(function($model, $key){
				return [$model->getKey() => $model->getAttributes()];
			});
		});
	}

	public function deleteFireCache()
	{
		if (isset($this->fire_caches))
			foreach($this->fire_caches as $key)
				Cache::forget($key);

		$min = static::getCacheIndex($this->getKey());
		$max = $min + 1;
		$key = $this->getTable().','.$min.'-'.$max;
		Cache::forget($key);
	}


	public static function findByCache($id)
	{
		if (!is_numeric($id)) return static::find($id);

		$index = static::getCacheIndex($id);
		$data = static:: getRangeCache($index);

		return $data->has($id) ? (new static)->setRawAttributes($data->get($id)) : false;
	}

	public static function findManyByCache(...$ids)
	{
		$ids = Arr::flatten($ids);
		$indices = array_unique(array_map(function($id) {
			return static::getCacheIndex($id);
		}, $ids));

		$data = [];
		foreach($indices as $index)
			$data = array_merge($data, static:: getRangeCache($index));

		$result = collect([]);
		foreach ($ids as $id) {
			if (!$data->has($id))
				continue;
			$result[] = (new static)->setRawAttributes($data->get($id));
		}

		return $result;
	}

}
