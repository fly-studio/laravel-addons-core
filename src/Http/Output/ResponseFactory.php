<?php

namespace Addons\Core\Http\Output;

use Lang;
use Throwable;
use BadMethodCallException;
use Illuminate\Http\Response;
use Addons\Core\Http\Output\Response\ApiResponse;
use Addons\Core\Http\Output\Response\TextResponse;
use Addons\Core\Http\Output\Response\OfficeResponse;
use Addons\Core\Http\Output\Response\ExceptionResponse;

class ResponseFactory {


	/**
	 * Return a new response from the application.
	 *
	 * @param string  $result
	 * @param ... 	see parameters of api/success/failure
	 * @return \Illuminate\Http\Response
	 */
	public function make(string $result, ...$config)
	{
		switch ($result) {
			case 'api':
			case 'office':
			case 'success':
			case 'error':
			case 'raw':
				return $this->$result(...$config);
		}

		throw new BadMethodCallException("OutputResponse method [{$result}] does not exist.");
	}

	public function raw($raw)
	{
		return $raw instanceOf Response ? $raw : new Response($raw);
	}

	public function api($data)
	{
		$response = new ApiResponse();

		return $response->data($data);
	}

	public function office(?array $data)
	{
		$response = new OfficeResponse();

		return $response->data($data);
	}

	public function exception(Throwable $e, string $messageName = null)
	{
		$response = new ExceptionResponse();
		$response
			->message($messageName)
			->withException($e);

		return $response;
	}

	public function success(string $messageName = null, $data = null)
	{
		return $this->text($messageName, $data)->code(0);
	}

	public function error(string $messageName = null, $data = null)
	{
		return $this->text($messageName, $data)->code(Response::HTTP_BAD_REQUEST);
	}

	protected function text(string $messageName = null, $data = null)
	{
		$response = new TextResponse();
		$response->message($messageName)->data($data);

		return $response;
	}

}
