<?php

namespace Addons\Core\Http;

use Lang;
use Exception;
use BadMethodCallException;
use Addons\Core\Http\Response\ApiResponse;
use Addons\Core\Http\Output\TipTypeManager;
use Addons\Core\Http\Response\TextResponse;
use Addons\Core\Http\Response\OfficeResponse;
use Addons\Core\Http\Response\ExceptionResponse;

class OutputResponseFactory {


	/**
	 * Return a new response from the application.
	 *
	 * @param string  $result
	 * @param ... 	see parameters of api/success/failure
	 * @return \Illuminate\Http\Response
	 */
	public function make($result, ...$config)
	{
		switch ($result) {
			case 'api':
			case 'office':
			case 'success':
			case 'error':
			case 'failure':
			case 'notice':
			case 'warning':
				return $this->$result(...$config);
		}

		throw new BadMethodCallException("OutputResponse method [{$result}] does not exist.");
	}

	public function api($data, $encrypted = false, $rsaType = 'public')
	{
		$response = new ApiResponse();
		return $response->setData($data, $encrypted, $rsaType);
	}

	public function office($data)
	{
		$response = new OfficeResponse();
		return $response->setData($data);
	}

	public function exception(Exception $e, $message_name = null, $tipType = false, $data = [] , $showData = false)
	{
		$response = new ExceptionResponse();
		$response
			->setMessage($message_name, $data)
			->setAutoTip($tipType)
			->withException($e);
		if ($showData) $response->setData($data);

		return $response;
	}

	public function success($message_name = null, $tipType = true, $data = [], $showData = true)
	{
		return $this->text('success', $message_name, $tipType, $data, $showData);
	}

	public function error($message_name = null, $tipType = false, $data = [], $showData = false)
	{
		return $this->text('error', $message_name, $tipType, $data, $showData);
	}

	public function failure($message_name = null, $tipType = false, $data = [], $showData = false)
	{
		return $this->text('failure', $message_name, $tipType, $data, $showData);
	}

	public function notice($message_name = null, $tipType = false, $data = [], $showData = false)
	{
		return $this->text('notice', $message_name, $tipType, $data, $showData);
	}

	public function warning($message_name = null, $tipType = false, $data = [], $showData = false)
	{
		return $this->text('warning', $message_name, $tipType, $data, $showData);
	}

	protected function text($result, $message_name = null, $tipType = false, $data = [], $showData = false)
	{
		$response = new TextResponse();
		$response
			->setResult($result)
			->setMessage($message_name, $data)
			->setAutoTip($tipType);
		if ($showData) $response->setData($data);

		return $response;
	}

}
