<?php

if (! function_exists('delay_unlink')) {
	function delay_unlink($path, $delay)
	{
		return;
		if (!file_exists($path)) return FALSE;

		$md5 = is_file($path) ? md5_file($path) : NULL;
		//Queue
		$job = (new Addons\Core\Jobs\DelayUnlink($path, $md5))->delay($delay);
		app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
	}
}

if (! function_exists('static_path')) {
	function static_path($path = '')
	{
		static $static;
		if (empty($static)) $static = base_path(config('app.static'));
		return normalize_path($static . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
	}
}

if (! function_exists('static_url')) {
	function static_url($url = '')
	{
		static $static;
		if (empty($static)) $static = trim(str_replace(array(base_path(), '\\'), array('', '/'), base_path(config('app.static'))), '/') ;
		return url($static . (!empty($url) ? '/'.$url : ''));
	}
}

if (! function_exists('repo')) {
	function repo($className)
	{
		return app('App\\Repositories\\'.studly_case($className).'Repository');
	}
}
