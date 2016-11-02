<?php
namespace Addons\Core\Models;
use Illuminate\Support\Str;
use Cache;
trait CacheTrait{

	/*
	 * forget these keys when fire
	 * 
	 * @var array
	 */
	protected $fire_caches = [];

	protected static function bootCacheTrait()
	{
		static::created(function($model) {
			$model->deleteFireCache();
		});
		static::updated(function($model) {
			$model->deleteFireCache();
		});
		static::deleted(function($model) {
			$model->deleteFireCache();
		});
		static::saved(function($model) {
			$model->deleteFireCache();
		});
	}

	protected function deleteFireCache()
	{
		foreach($this->fire_caches as $key)
			Cache::forget($key);

		$min = floor($this->getKey() / 1000); $max = $min + 1;
		$key = $this->getTable().','.$min.'-'.$max;
		Cache::forget($key);
	}

	public static function findByCache($id)
	{
		$model = new static();
		$keyName = $model->getKeyName();
		$min = floor($id / 1000); $max = $min + 1;
		$key = $model->getTable().','.$min.'-'.$max;
		$data = Cache::remember($key, 7 * 24 * 60, function() use($min, $max, $keyName){ // 7 days
			$models = static::where($keyName, '>=', $min * 1000)->where($keyName, '<', $max * 1000)->get();
			return $models->keyBy($keyName);
		});
		return $data->get($id);
	}

}

