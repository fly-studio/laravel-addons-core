<?php

namespace Addons\Core\Exceptions;

use Addons\Core\Http\Output\ResponseFactory;
use Addons\Core\Http\Output\Response\TextResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class OutputResponseException extends HttpResponseException {

	public function __construct($messageNameOrInstance = null)
	{
		$this->response = $messageNameOrInstance instanceof TextResponse ? $messageNameOrInstance : app(ResponseFactory::class)->error($messageNameOrInstance);
	}

	public function headers($headers)
	{
		$this->response->setHeaders($headers);
		return $this;
	}

	public function request($request)
	{
		$this->response->request($request);
		return $this;
	}

	public function code(int $code, $text = null)
	{
		$this->response->code($code, $text);
		return $this;
	}

	public function of(string $of)
	{
		$this->response->of($of);
		return $this;
	}

	public function data($data)
	{
		$this->response->data($data);
		return $this;
	}

	public function message(string $messageName, array $transData = [])
	{
		$this->response->message($messageName, $transData);
		return $this;
	}

	public function rawMessage($message)
	{
		$this->response->rawMessage($message);
		return $this;
	}
}
