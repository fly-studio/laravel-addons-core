<?php

namespace Addons\Core\Tools;

use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Office {

	public static function excel(array $data, string $ext = 'xlsx', string $filepath = null)
	{
		$excel = new Spreadsheet();
		$excel->setActiveSheetIndex(0);
		$sheet = $excel->getActiveSheet();

		array_walk($data, function(&$v){
			foreach ($v as $key => $value)
				!is_scalar($value) && $v[$key] = @strval($value);
		});

		$sheet->fromArray($data);

		if (is_null($filepath))
			$filepath = tempnam(utils_path('files'), 'excel-');

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

		return $filepath;
	}

	public static function csv(array $data, string $filepath = null)
	{
		return self::excel($data ,'csv', $filepath);
	}

	public static function xls(array $data, string $filepath = null)
	{
		return self::excel($data ,'xls', $filepath);
	}

	public static function xlsx(array $data, string $filepath = null)
	{
		return self::excel($data ,'xlsx', $filepath);
	}

}
