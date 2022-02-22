<?php

namespace Addons\Core\Http;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Routing\Router as BaseRouter;

class Router extends BaseRouter {

	/**
	 * version for api
	 * @example $router->api('v1', function($router){ });
	 * @example this is equal: $router->group(['prefix' => 'v1', 'namespace' => 'Api\\V1'], $callback);
	 *
	 * @param  [type]  $version  the api's version
	 * @param  Closure $callback [description]
	 * @return [type]            [description]
	 */
	public function api($version, $second, $third = null)
	{
		if (func_num_args() == 2)
			list($version, $callback, $attributes) = array_merge(func_get_args(), [[]]);
		else
			list($version, $attributes, $callback) = func_get_args();
		$_attributes = ['prefix' => $version, 'namespace' => 'Api\\'.Str::studly($version)];
		$attributes = array_merge($_attributes, $attributes);
		$this->group($attributes, $callback);
	}

	/**
	 * crud routes
	 * include resource route, and data/export/print
	 *
	 * @example $this->crud('member', MemeberController::class);
	 *
	 * @param  [string] $uri
	 * @param  [string] $controller class name
	 */
	public function crud(string $uri, string $controller)
	{
		$this->get($uri.'/data', [$controller, 'data']);
		$this->post($uri.'/data', [$controller, 'data']);
		$this->get($uri.'/export', [$controller, 'export']);
		$this->get($uri.'/print', [$controller, 'print']);
		$this->resource($uri, $controller);
	}

	/**
	 * action
	 *
	 * @example $this->action('user', User::class, ['index', 'edit', 'delete']);
	 * @example $this->action(user', User::class, ['index', 'e' => 'edit', 'd' => 'delete']);
	 *
	 * @param  [string] $uri
	 * @param  [string] $controller
	 * @param  [array] $actions
	 * @param  [string] $method
	 */
	public function action(string $uri, string $controller, array $actions, $method = 'any')
	{
		foreach ($actions as $name => $action)
		{
			if (is_numeric($name))
				$name = $action;
			$this->$method($uri.($name == 'index' ? '' : '/'.$name), [$controller, Str::camel($action)]);
		}
	}

}
