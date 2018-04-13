<?php

namespace Addons\Core\Middleware;

use Closure;
use Illuminate\Http\Response;

class CrossDomain
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next, $domain = '*')
	{

		$response = strtoupper($request->method()) == 'OPTIONS' ? new Response() : $next($request);

		$response->headers->set('Access-Control-Allow-Origin', $domain);
		$response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE, PUT, PATCH, HEAD');
		$response->headers->set('Access-Control-Allow-Credentials', 'true');
		$response->headers->set('Access-Control-Allow-Headers', 'accept, accept-encoding, authorization, content-type, dnt, origin, user-agent, x-csrftoken, x-requested-with, cookie');

		return $response;
	}
}
