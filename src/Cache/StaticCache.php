<?php

namespace Addons\Core\Cache;

class StaticCache {

	private static $instances = [];

	/**
	 * Like Cache::remember, but this via static variant
	 *
	 * @param  string   $key      a unique key
	 * @param  int      $expired  this data expire in seconds
	 * @param  callable $callback data
	 * @return
	 */
	public static function remember(string $key, int $expired, callable $callback)
	{
		if (isset(static::$instances[$key]['last']))
		{
			if (time() - $expired <= static::$instances[$key]['last'])
				return static::$instances[$key]['data'];
		}

		$data = call_user_func($callback);

		static::$instances[$key] = [
			'last' => time(),
			'data' => $data
		];

		return $data;
	}

	public static function forget(string $key)
	{
		unset(static::$instances[$key]);
	}

}
