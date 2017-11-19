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
	 * @example $this->crud('member', 'MemeberController');
	 * @example $this->crud(['member' => 'MemberController', 'role' => 'RoleController'])
	 *
	 * @param  [array|string] $route_name
	 * @param  [string] $controller NULL or controller
	 */
	public function crud($name, $controller = NULL)
	{
		$list = !is_array($name) ? [$name => $controller] : $name;
		foreach($list as $name => $controller)
		{
			$this->get($name.'/data', $controller.'@data');
			$this->post($name.'/data', $controller.'@data');
			$this->get($name.'/export', $controller.'@export');
			$this->get($name.'/print', $controller.'@print');
			$this->resource($name, $controller);
		}
	}

	/**
	 * actions
	 *
	 * @example $this->actions(['member' => ['index', 'edit', 'delete']]);
	 * @example $this->actions(['member' => ['index', 'e' => 'edit', 'd' => 'delete']]);
	 *
	 * @param  [string] $controllers
	 * @param  [string] $method
	 */
	public function actions(array $controllers, $method = 'any')
	{
		foreach ($controllers as $name => $actions)
		{
			$controller = Str::studly($name).'Controller';
			foreach($actions as $k => $action)
			{
				is_numeric($k) && $k = $action;
				$this->$method($name.($k == 'index' ? '' : '/'.$k), $controller.'@'.Str::camel($action));
			}
		}
	}

}