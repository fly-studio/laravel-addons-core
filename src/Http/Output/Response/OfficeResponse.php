<?php

namespace Addons\Core\Http\Output\Response;

use Addons\Core\File\Mimes;
use Addons\Core\Tools\Office;
use Symfony\Component\HttpFoundation\Request;
use Addons\Core\Http\Output\Response\TextResponse;

class OfficeResponse extends TextResponse {

	public function getOf()
	{
		if ($this->of == 'auto')
		{
			$request = app('request');
			$of = $request->input('of', null);
			if (!in_array($of, ['csv', 'xls', 'xlsx']))
				$of = 'xlsx';

			return $of;
		}

		return $this->of;
	}

	public function getOutputData()
	{
		return $this->getData();
	}

	public function prepare(Request $request)
	{
		$data = $this->getOutputData();
		$of = $this->getOf();
		$response = null;

		switch ($of) {
			case 'csv':
			case 'xls':
			case 'xlsx':
				$filename = Office::$of($data);

				$response = response()->download($filename, date('YmdHis').'.'.$of, ['Content-Type' =>  Mimes::getInstance()->mime_by_ext($of)])
					->deleteFileAfterSend(true)
					->setStatusCode($this->getStatusCode());
				break;
		}

		return $response;
	}

}
