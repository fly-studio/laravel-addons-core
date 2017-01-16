<?php
namespace Addons\Core;

use Illuminate\Support\Str;
use Addons\Core\Http\UrlGenerator;
use Symfony\Component\Finder\Finder;
use Addons\Core\Validation\Validator;
use Addons\Core\Http\ResponseFactory;
use Addons\Core\Events\EventDispatcher;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
	/**
	 * 指定是否延缓提供者加载。
	 *
	 * @var bool
	 */
	protected $defer = false;
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

		/*$this->app->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app) {
			return new ResponseFactory($app['Illuminate\Contracts\View\Factory'], $app['redirect']);
		});*/
		//replace class
		$this->app->bind('Illuminate\Contracts\Routing\ResponseFactory', ResponseFactory::class);
		$this->app->bind('Illuminate\Contracts\Routing\UrlGenerator', UrlGenerator::class);

		$this->mergeConfigFrom(__DIR__ . '/../config/mimes.php', 'mimes');
		//$this->mergeConfigFrom(__DIR__ . '/../config/socketlog.php', 'socketlog');
		$this->mergeConfigFrom(__DIR__ . '/../config/plugin.php', 'plugin');

		$this->registerPlugins();
	}

	private function registerPlugins()
	{
		//自动加载plugins下的配置，和ServiceProvider	
		$loader = require SYSPATH.'/vendor/autoload.php';
		$original_config = config('plugin');config()->offsetUnset('plugin');
		$router = $this->app['router'];
		$kernel = $this->app[\Illuminate\Contracts\Http\Kernel::class];
		//$consoleKernel = $this->app[\Illuminate\Contracts\Console\Kernel::class];
		$paths = [base_path('vendor')];
		is_dir(PLUGINSPATH.'vendor') && array_unshift($paths, PLUGINSPATH.'vendor');
		foreach (Finder::create()->directories()->in($paths)->depth(0) as $path)
		{
			$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			//read config
			$file = $path.'config'.DIRECTORY_SEPARATOR.'plugin.php';
			$config = array_merge($original_config, file_exists($file) ? require($file) : []);
			if (!$config['enable']) continue;
			//set path name namespace
			$config['path'] = $path;
			$config['name'] = $name = !empty($config['name']) ? $config['name'] : basename(rtrim($path, DIRECTORY_SEPARATOR));
			$config['namespace'] = $namespace = !empty($config['namespace']) ? $config['namespace'] : 'Plugins\\'.Str::studly($name);
			//set psr-4
			$loader->setPsr4($namespace.'\\App\\', array($path.'app'));
			$loader->setPsr4($namespace.'\\', array($path));
			//set config
			config()->set('plugins.'.$name, $config);
			config()->set('smarty.template_path', (array)config('smarty.template_path', []) + [$name => $path.'resources/views']);

			//read config
			!empty($config['register']['validation']) && $this->mergeConfigFrom($config['path'].'config/validation.php', 'validation');
			foreach ($config['config'] as $file)
				$this->mergeConfigFrom($config['path'].'config/'.$file.'.php', $file);

			//register middleware
			foreach ($config['routeMiddleware'] as $key => $middleware)
				$router->middleware($key, $middleware);
			foreach ($config['middlewareGroups'] as $group => $middlewares)
				foreach($middlewares as $middleware)
					$router->pushMiddlewareToGroup($group, $middleware);

			//bind main middleware.
			//use middlewareGroups instead. remove it at 2017-01-04
			//if (!empty($config['middleware']))
			//	foreach($config['middleware'] as $middleware)
			//		$kernel->pushMiddleware($middleware);
			// or
			//!empty($config['middleware']) && set_property($kernel, 'middleware', array_merge(get_property($kernel, 'middleware'), $config['middleware']));
			//register commands


			//这里提供更加灵活的plugins/ServiceProvider.php的配置方式，注意$config['register']中配置所对应的程序会优先于plugins/ServiceProvider.php
			$provider = $namespace.'\ServiceProvider';
			file_exists($path.'ServiceProvider.php') && $this->app->register(new $provider($this->app));
		}
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([__DIR__ . '/../config/attachment.php' => config_path('attachment.php')], 'config');
		$this->publishes([__DIR__ . '/../config/mimes.php' => config_path('mimes.php')], 'config');
		$this->publishes([__DIR__ . '/../config/validation.php' => config_path('validation.php')], 'config');
		//$this->publishes([__DIR__ . '/../config/socketlog.php' => config_path('socketlog.php')], 'config');

		$this->app['translator']->addNamespace('core', realpath(__DIR__.'/../resources/lang/'));


		$this->app['validator']->resolver( function( $translator, $data, $rules, $messages = [], $customAttributes = []) {
			return new Validator( $translator, $data, $rules, $messages, $customAttributes );
		});

		$this->bootPlugins();
	}

	private function bootPlugins()
	{
		$router = $this->app['router'];
		$plugins = config('plugins');
		if (empty($plugins)) return;
		foreach($plugins as $name => $config)
		{
			$_c = !empty($config['register']['config']) ? $config['config'] : [];
			!empty($config['register']['validation']) && $_c[] = 'validation';
			foreach ($_c as $file)
				$this->publishes([$config['path'].'config/'.$file.'.php' => config_path($file.'.php')], 'config');

			!empty($config['register']['view']) && $this->loadViewsFrom(realpath($config['path'].'resources/views/'), $name);
			!empty($config['register']['translator']) && $this->loadTranslationsFrom(realpath($config['path'].'resources/lang/'), $name);
			if (!empty($config['register']['migrate']) && $this->app->runningInConsole())
				$this->loadMigrationsFrom(realpath($config['path'].'database/migrations'));
			if ($config['register']['router'])
				foreach($config['router'] as $key => $route)
				{
					$router->group(['namespace' => empty($route['namespace']) ? $config['namespace'].'\App\Http\Controllers' : $route['namespace'], 'middleware' => array_merge([$key], $route['middleware']), 'prefix' => $route['prefix']], function($router) use ($config, $key) {
						require $config['path'].'routes/'.$key.'.php';
					});
				}
			!empty($config['commands']) && $this->commands($config['commands']);

			if (!empty($config['register']['console']))
				require $config['path'].'routes/console.php';

			if (!empty($config['register']['event']))
				app(EventDispatcher::class)->group(['namespace' => $config['namespace'].'\App'], function($eventer) use($config) {
					require $config['path'].'routes/event.php';
				});

		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['core'];
	}
}