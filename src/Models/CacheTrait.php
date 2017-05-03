<?php
namespace Addons\Core\Models;
use Cache;
trait CacheTrait{

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

	public function deleteFireCache()
	{
		if (isset($this->fire_caches))
			foreach($this->fire_caches as $key)
				Cache::forget($key);

		$min = floor($this->getKey() / 1000); $max = $min + 1;
		$key = $this->getTable().','.$min.'-'.$max;
		Cache::forget($key);
	}

	public static function findByCache($id)
	{
		if (!is_numeric($id)) return static::find($id);
		$model = new static();
		$keyName = $model->getKeyName();
		$min = floor($id / 1000); $max = $min + 1;
		$key = $model->getTable().','.$min.'-'.$max;
		$data = Cache::remember($key, config('cache.ttl'), function() use($min, $max, $keyName){ // 7 days
			$models = static::where($keyName, '>=', $min * 1000)->where($keyName, '<', $max * 1000)->get();
			return $models->mapWithKeys(function($model, $key){
				return [$model->getKey() => $model->getAttributes()];
			});
		});
		return $data->has($id) ? (new static)->setRawAttributes($data->get($id)) : false;
	}

}

