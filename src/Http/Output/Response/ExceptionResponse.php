<?php

namespace Addons\Core\Http\Output\Response;

use Exception;
use Illuminate\Support\Arr;
use Addons\Core\Http\Output\Response\TextResponse;

class ExceptionResponse extends TextResponse {

	public function getException()
	{
		return $this->exception;
	}

	public function getOutputData()
	{
		$data = parent::getOutputData();

		if (config('app.debug'))
		{
			$data += [
				'exception' => get_class($this->exception),
				'file' => $this->exception->getFile(),
				'line' => $this->exception->getLine(),
				'trace' => collect($this->exception->getTrace())->map(function ($trace) {
					return Arr::except($trace, ['args']);
				})->all(),
			];
		}

		return $data;
	}


}
