<?php

namespace Addons\Core\Middleware;

use Closure;
use Illuminate\Http\Response;
use Addons\Core\Tools\OutputEncrypt;

class Encrypt
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
		if (strtoupper($request->method()) == 'OPTIONS')
		{
			$response = new Response();

			$e = new OutputEncrypt();
			$response->headers->set('X-RSA', $e->getRsaPublicKey());
			$response->headers->set('X-KEY', $e->getServerEncryptedKey());

			return $response;
		}
		else
			return $next($request);
	}
}
