<?php

namespace Addons\Core\Http\Response;

use Exception;
use Illuminate\Support\Arr;
use Addons\Core\Http\Response\TextResponse;

class ExceptionResponse extends TextResponse {

	protected $result = 'error';

	public function getException()
	{
		return $this->exception;
	}

	public function getMessage()
	{
		$code = $this->getStatusCode();
		$message = $this->exception->getMessage() ?? (
			$code != 200 && Lang::has('exception.http.'.$code)
				? trans('exception.http.'.$code)
				: trans('core::common.default.' . $this->getResult())
			);
		return empty($this->message) ? $message : $this->message;
	}

	public function getOutputData()
	{
		$data = parent::getOutputData();

		$data +=
			config('app.debug')
			? [
				'exception' => get_class($this->exception),
				'file' => $this->exception->getFile(),
				'line' => $this->exception->getLine(),
				'trace' => collect($this->exception->getTrace())->map(function ($trace) {
					return Arr::except($trace, ['args']);
				})->all(),
			]
			: [];
		return $data;
	}


}
