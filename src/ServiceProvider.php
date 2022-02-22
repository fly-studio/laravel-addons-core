<?php
namespace Addons\Core;

use Addons\Core\Cache\RWRedis;
use Addons\Core\Http\ResponseFactory;
use Addons\Core\Http\Middleware\EncryptBody;
use Addons\Core\Http\Middleware\CrossDomain;
use Addons\Core\Coroutine\Database\MySqlConnection;
use Addons\Core\Coroutine\Database\Connectors\MySqlConnector;
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
		//replace class
		$this->app->bind('Illuminate\Contracts\Routing\ResponseFactory', ResponseFactory::class);

		$this->mergeConfigFrom(__DIR__ . '/../config/mimes.php', 'mimes');

		//register router middleware
		$router = $this->app['router'];
		$router->aliasMiddleware('cross-domain', CrossDomain::class);
		$router->aliasMiddleware('encrypt-body', EncryptBody::class);

		$this->app->instance('path.vendor', realpath(__DIR__.'/../../../'));

	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([__DIR__ . '/../config/mimes.php' => config_path('mimes.php')], 'config');

		$this->app['translator']->addNamespace('core', realpath(__DIR__.'/../resources/lang/'));
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
