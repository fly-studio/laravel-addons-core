<?php

namespace Addons\Core\File;

class Mimes {

	private $mimes;
	 /**
	 * The singleton instance.
	 *
	 * @var ExtensionGuesser
	 */
	private static $instance = null;

	public function __construct()
	{
		$this->mimes = (array)config('mimes');
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @return ExtensionGuesser
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function mime_by_ext($ext)
	{
		return isset($this->mimes[strtolower($ext)]) ? $this->mimes[strtolower($ext)][0] : false;
	}

	public function ext_by_mime($mime)
	{
		foreach ($this->mimes as $key => $value)
			if (in_array($mime, $value)) return $key;
		return $key;
	}

	public function get_mimes()
	{
		return $this->mimes;
	}
}
