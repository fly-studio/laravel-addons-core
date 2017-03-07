<?php
namespace Addons\Core\Http;

use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Str;
use Closure;
class Router extends BaseRouter {

	/**
	 * version for api
	 * @example $router->version('v1', function($router){ });
	 * @example this is equal: $router->group(['prefix' => 'v1', 'namespace' => 'Api\\V1'], $callback);
	 * 
	 * @param  [type]  $version  the api's version
	 * @param  Closure $callback [description]
	 * @return [type]            [description]
	 */
	public function api($version, $second, $third = null)
	{
		if (func_num_args() == 2) {
            list($version, $callback, $attributes) = array_merge(func_get_args(), [[]]);
        } else {
            list($version, $attributes, $callback) = func_get_args();
        }
        $_attributes = ['prefix' => $version, 'namespace' => 'Api\\'.Str::studly($version)];
        $attributes = array_merge($_attributes, $attributes);
		$this->group($attributes, $callback);
	}

	/**
	 * 添加后台的路由
	 * 包含resource路由 以及$route_name/[data|print|export]/[json|xml|xls|xlsx|...]/
	 * @example $router->addAdminRoutes('admin/member', 'Admin\\MemberController');
	 * @example $router->addAdminRoutes(['admin/member' => 'Admin\\MemberController', ...]); 可以传递数组
	 * 
	 * 
	 * @param mixed $route_name      路由名，参考resources
	 * @param string $controller_name 当$route_name不为数组是控制器名
	 */
	public function addAdminRoutes($route_name, $controller_name = NULL)
	{
		$list = !is_array($route_name) ? [$route_name => $controller_name] : $route_name;
		foreach($list as $route_name => $controller_name)
		{
			$this->resource($route_name, $controller_name);

			//admin/ctrl/data,print,export/json
			$this->match(['post', 'get'], $route_name.'/{action}/{of}/{jsonp?}', function($action, $of, $jsonp = NULL) use($controller_name){
				app('request')->offsetSet('of', $of);
				app('request')->offsetSet('jsonp', $jsonp);
				return $this->callbackUndefinedRoute($controller_name, $action, true);
			})->where('action', '(data|print|export)');

			$this->match(['post', 'put', 'patch', 'delete'], $route_name.'/{id}/{action}', function($id, $action) use($controller_name) {
				app('request')->offsetSet('id', $id);
				return $this->callbackUndefinedRoute($controller_name, $action, true);
			});
		}
			
	}
	/**
	 * 添加默认路由
	 * @example $router->addUndefinedRoutes() 匹配所有未定义的路由 比如：user/index home/index user/xxx
	 * 
	 */
	public function addUndefinedRoutes()
	{
		$this->any('{ctrl?}/{action?}', function($ctrl = 'home', $action = 'index') {
			return $this->callbackUndefinedRoute($ctrl, $action);
		});
	}

	/**
	 * 添加一个未知action的路由
	 * @example $router->addUndefinedRoutes('member') 匹配所有member/*的路由(MemberController)  比如：member/create member/destory 
	 * @example $router->addUndefinedRoutes('member', 'UserController') 可以指定一个Controller
	 * @example $router->addUndefinedRoutes(['member' => 'MemberController', 'user' => NULL, 'tools']) 参数可以为数组，值为NULL，或者无键名同example 1
	 * 
	 * @param mixed $route_name      路由名，可以为数组，字符串
	 * @param string $controller_name 控制器名，如果$route_name为字符串，这里不填写则会使用$route_name猜测一个Controller
	 */
	public function addAnyActionRoutes($route_name, $controller_name = NULL)
	{
		$list = !is_array($route_name) ? [$route_name => $controller_name] : $route_name;
		foreach($list as $route_name => $controller_name)
		{
			if (is_numeric($route_name) && !empty($controller_name)) {$route_name = $controller_name; $controller_name = NULL;}
			$this->any($route_name.'/{action?}', function($action = 'index') use ($route_name, $controller_name){
				return $this->callbackUndefinedRoute(!empty($controller_name) ? $controller_name : $route_name, $action, !empty($controller_name));
			});
		}
	}

	private function callbackUndefinedRoute($ctrl, $action, $ctrl_is_class = FALSE)
	{
		//能执行到本过程，表示路由已经匹配到，则可以直接获取当前匹配的路由的配置
		$route = $this->getCurrentRoute();
		$namespace = $route->getAction()['namespace'];
		if (!$ctrl_is_class)
		{
			$ctrls = explode('/', $ctrl);
			$ctrls = array_map(function($v){
				return Str::studly($v);
			}, $ctrls);
			$className = $namespace.'\\'.implode('\\', $ctrls).'Controller';
		}
		else 
			$className = $namespace.'\\'.$ctrl;
		
		//!class_exists($className) && $className = 'Addons\\Core\\Controllers\\'.implode('\\', $ctrls).'Controller';
		$action = Str::camel($action);
		(!class_exists($className) ||  !method_exists($className, $action)) && abort(404);

		$class = new \ReflectionClass($className);
		$function = $class->getMethod($action); //ReflectionMethod
		$route_parameters = $route->resolveMethodDependencies(
			$route->parametersWithoutNulls(), $function
		);
		$parameters = $function->getParameters(); //ReflectionParameter 
		
		$_data = array();
		$request = app('request');
		for ($i = 0; $i < count($parameters); $i++)
		{ 
			$key = $parameters[$i]->getName();
			if ( array_key_exists($i, $route_parameters) )
			{
				$_data[] = $route_parameters[$i];
			}
			else if ( array_key_exists($key, $route_parameters) )
			{
				$_data[] = $route_parameters[$key];
			}
			else if ($parameters[$i]->getClass()) //just in $route_parameters;
			{
				$_data[] = app($parameters[$i]->getClass()->name);
			}
			else //from $_GET
			{ 
				$default = $parameters[$i]->isDefaultValueAvailable() ? $parameters[$i]->getDefaultValue() : NULL;
				$_data[] = array_key_exists($key, $_GET) ? $request->input($key) : $default;
			}

		}
		$obj = app()->make($className);
		// Execute the action itself
		return $obj->callAction($action, $_data);
	}
}