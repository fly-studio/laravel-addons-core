<?php

namespace Addons\Core\Middleware;

use Closure;
use Illuminate\Http\Response;

class NoCache
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);
		
		$response->headers->set('P3P','CP="CAO PSA OUR"');
		if(in_array(strtoupper($request->method()), ['POST', 'PUT', 'DELETE'])){
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
