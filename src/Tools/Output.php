<?php
namespace Addons\Core\Tools;

use Addons\Core\Spyc;

class Output {

	public static function json($data)
	{
		return json_encode($data);
	}

	public static function jsonp($data, $callback = 'callback')
	{
		return htmlspecialchars($callback).'.call(this,'.self::json($data).');';
	}

	public static function js($data)
	{
		return 'var RESULT = '.self::json($data).';if (self != parent) parent.RESULT = self.RESULT;';
	}

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

	public static function text($data)
	{
		is_array($data) && $data = var_export($data, TRUE);
		return $data;
	}

	public static function txt($data)
	{
		return self::text($data);
	}

	public static function xml($data)
	{
		return xml_encode($data);
	}

	public static function yaml($data)
	{
		$filepath = tempnam(storage_path('utils'), 'yaml');
		@chmod($filepath, 0777);
		file_put_contents($filepath, function_exists('yaml_emit') ? yaml_emit($data) : Spyc::YAMLDump($data));
		return $filepath;
	}


}