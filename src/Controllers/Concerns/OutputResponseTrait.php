<?php

namespace Addons\Core\Controllers\Concerns;

use Addons\Core\Http\Output\Response\ApiResponse;
use BadMethodCallException;
use Addons\Core\Http\Output\Response\OfficeResponse;
use Addons\Core\Http\Output\Response\TextResponse;
use Addons\Core\Http\Output\ResponseFactory;

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

    public function api($data): ApiResponse {
        return app(ResponseFactory::class)->make('api', ...func_get_args());
    }

    public function office(?array $data): OfficeResponse {
        return app(ResponseFactory::class)->make('office', ...func_get_args());
    }

    public function success(string $messageName = null, $data = null): TextResponse {
        return app(ResponseFactory::class)->make('success', ...func_get_args());
    }

    public function error(string $messageName = null, $data = null): TextResponse {
        return app(ResponseFactory::class)->make('error', ...func_get_args());
    }
}
