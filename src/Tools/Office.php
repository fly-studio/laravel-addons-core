<?php

namespace Addons\Core\Tools;

use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Office {

	public static function excel($data, $ext = 'xlsx', $filepath = TRUE)
	{
		$excel = new Spreadsheet();
		$excel->setActiveSheetIndex(0);
		$sheet = $excel->getActiveSheet();
		array_walk($data, function(&$v){
			foreach ($v as $key => $value)
				!is_scalar($value) && $v[$key] = @strval($value);
		});
		$sheet->fromArray($data);

		$filepath == TRUE && $filepath = tempnam(storage_path('utils'),'excel');
		@chmod($filepath, 0777);
		switch (strtolower($ext)) {
			case 'xlsx':
				$objWriter = new Xlsx($excel);
				break;
			case 'xls':
				$objWriter = new Xls($excel);
				break;
			case 'csv':
				$objWriter = new Csv($excel);
				break;
			default:
				# code...
				break;
		}
		$objWriter->save($filepath);
		//@unlink($filepath);
		return $filepath;
	}

	public static function csv($data)
	{
		return self::excel($data ,'csv');
	}

	public static function xls($data)
	{
		return self::excel($data ,'xls');
	}

	public static function xlsx($data)
	{
		return self::excel($data ,'xlsx');
	}

}