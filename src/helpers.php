<?php

if (! function_exists('static_path')) {
	function static_path(string $path = '')
	{
		return normalize_path(config('app.static_path') . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
	}
}

if (! function_exists('utils_path'))
{
	function utils_path(string $path = '')
	{
		return normalize_path(config('app.utils_path') . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
	}
}

if (! function_exists('static_url')) {
	function static_url(string $url = '')
	{
		static $static;

		if (empty($static))
		{
			$static = trim(
				str_replace([public_path(), '\\'], ['', '/'], static_path()),
				'/'
			);
		}

		return url()->asset($static . (!empty($url) ? '/'.$url : ''));
	}
}

if (! function_exists('repo')) {
	function repo(string $className)
	{
		return app('App\\Repositories\\'.studly_case($className).'Repository');
	}
}


if (! function_exists('static_cache')) {
	/**
	 * Like Cache::remember($key, $expired, $callback), but this via static variant to store the data
	 *
	 * @param  string   $key      a unique key
	 * @param  int      $expired  this data expire in seconds
	 * @param  callable $callback data
	 * @return
	 */
	function static_cache(string $key, int $expired, callable $callback)
	{
		return \Addons\Core\Cache\StaticCache::remember($key, $expired, $callback);
	}
}
