<?php

namespace Addons\Core\Controllers\Concerns;

use BadMethodCallException;
use Addons\Core\Http\Output\ResponseFactory;
use Addons\Core\Exceptions\OutputResponseException;

/**
 * Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException	403
 * Symfony\Component\HttpKernel\Exception\BadRequestHttpException	400
 * Symfony\Component\HttpKernel\Exception\ConflictHttpException	409
 * Symfony\Component\HttpKernel\Exception\GoneHttpException	410
 * Symfony\Component\HttpKernel\Exception\HttpException	500
 * Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException	411
 * Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException	405
 * Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException	406
 * Symfony\Component\HttpKernel\Exception\NotFoundHttpException	404
 * Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException	412
 * Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException	428
 * Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException	503
 * Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException	429
 * Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException	401
 * Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException	415
 */
trait OutputResponseTrait {

	public function api($data)
	{
		return app(ResponseFactory::class)->make('api', ...func_get_args());
	}

	public function office(?array $data)
	{
		return app(ResponseFactory::class)->make('office', ...func_get_args());
	}

	public function success(string $messageName = null, $data = null)
	{
		return app(ResponseFactory::class)->make('success', ...func_get_args());
	}

	public function error(string $messageName = null, $data = null)
	{
		return app(ResponseFactory::class)->make('error', ...func_get_args());
		//抛出失败，终止运行，因为需要返回一个response做链式的修改，所以抛出异常的方式废止
		//throw new OutputResponseException($response);
	}
}
