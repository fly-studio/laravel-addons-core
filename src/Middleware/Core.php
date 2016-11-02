<?php

namespace Addons\Core\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;
class Core
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [];
	/**
	 * Create a new filter instance.
	 *
	 * @param  \Addons\Core\Tools\Wechat\Account  $auth
	 * @return void
	 */
	public function __construct(Application $app)
	{
        $this->app = $app;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		foreach ($this->except as $except) {
            if ($request->is($except)) {
                config()->set('session.driver', 'array');
            }
        }

		$response = $next($request);
		
		$response->headers->set('P3P','CP="CAO PSA OUR"');
		if(in_array($request->method(), array( 'POST', 'PUT', 'DELETE' ))){
			//header no cache when post
			foreach([
				'Expires' => '0',
				'Cache-Control' => 'no-store,private, post-check=0, pre-check=0, max-age=0',
				'Pragma' => 'no-cache',
			] as $k => $v)
				$response->headers->set($k, $v);
		}

		return $response;
	}
}
