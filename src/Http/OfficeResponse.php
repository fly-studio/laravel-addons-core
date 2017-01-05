<?php
namespace Addons\Core\Http;

use Addons\Core\Http\OutputResponse;
use Addons\Core\Tools\OutputEncrypt;
use Addons\Core\Tools\Office;

class OfficeResponse extends OutputResponse {

	protected $type = 'export';

	public function getFormatter()
	{
		if ($this->formatter == 'auto')
		{
			$request = app('request');
			$of = $request->input('of', null);
			if (!in_array($of, ['csv', 'xls', 'xlsx', 'pdf']))
				$of = 'xlsx';
			return $of;
		}
		return $this->formatter;
	}

	public function getOutputData()
	{
		return $this->getData();
	}

	public function prepare(Request $request)
	{
		$data = $this->getOutputData();
		$of = $this->getFormatter();
		$response = null;
		switch ($of) {
			case 'csv':
			case 'xls':
			case 'xlsx':
			case 'pdf': //download
				$filename = Office::$of($data);
				$response = response()->download($filename, date('YmdHis').'.'.$of, ['Content-Type' =>  Mimes::getInstance()->mime_by_ext($of)])->deleteFileAfterSend(true)->setStatusCode($this->getStatusCode());
				break;
		}
		return $response;
	}

}
