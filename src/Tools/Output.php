<?php
namespace Addons\Core\Tools;

use Addons\Func\Tools\Spyc;

class Output {

	public static function js($data)
	{
		return 'var RESULT = '.json_encode($data).';if (self != parent) parent.RESULT = self.RESULT;';
	}

	public static function text($data)
	{
		is_array($data) && $data = var_export($data, true);
		return $data;
	}

	public static function txt($data)
	{
		return self::text($data);
	}

	public static function xml($data)
	{
		$data = json_decode(json_encode($data), true);
		return xml_encode($data);
	}

	public static function yaml($data)
	{
		return function_exists('yaml_emit') ? yaml_emit($data) : Spyc::YAMLDump($data);
	}
}