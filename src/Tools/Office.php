<?php
namespace Addons\Core\Tools;

class Office {

	public static function excel($data, $ext = 'xlsx', $filepath = TRUE)
	{
		$excel = new \PHPExcel();
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
				$objWriter = new \PHPExcel_Writer_Excel2007($excel);
				break;
			case 'xls':
				$objWriter = new \PHPExcel_Writer_Excel5($excel);
				break;
			case 'csv':
				$objWriter = new \PHPExcel_Writer_CSV($excel);
				break;
			case 'pdf':
				$objWriter = new \PHPExcel_Writer_PDF($excel);
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

	public static function pdf($data)
	{
		return self::excel($data ,'pdf');
	}
}