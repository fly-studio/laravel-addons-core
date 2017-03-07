<?php

namespace Addons\Core\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Addons\Core\Http\OutputResponse;
use RuntimeException;

class OutputResponseException extends HttpResponseException{

	public function __construct($message_name = null, $result = 'failure')
	{
		if ($message_name instanceof OutputResponse) {
			$this->response = $message_name;
		} else {
			$this->response = new OutputResponse;
			!empty($message_name) && $this->setMessage($message_name);
		}
		$this->setResult($result);
	}

	public function setResult($result)
	{
		$this->response->setResult($result);
		return $this;
	}

	public function setStatusCode($code)
	{
		$this->response->setStatusCode($code);
		return $this;
	}

	public function setFormatter($formatter)
	{
		$this->response->setFormatter($formatter);
		return $this;
	}

	public function setUrl($url)
	{
		$this->response->setUrl($url);
		return $this;
	}

	public function setData($data, $outputRaw = false)
	{
		$this->response->setData($data, $outputRaw);
		return $this;
	}

	public function setMessage($message_name, $transData = [])
	{
		$this->response->setMessage($message_name, $transData);
		return $this;
	}

	public function setRawMessage($message)
	{
		$this->response->setRawMessage($message);
		return $this;
	}
}