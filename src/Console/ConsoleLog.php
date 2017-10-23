<?php

namespace Addons\Core\Console;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleLog {

	static $consoleOutput;
	static $daemon = false;
	static $debug = true;

	public static function write($type, $message)
	{
		if (static::$daemon)
		{
			logger()->$type($message);
		}
		else if (app()->runningInConsole())
		{
			if (!static::$debug && $type == 'debug') return;
			$argv = implode(' ', $_SERVER['argv']);
			if (strpos($argv, 'queue:') !== false) // in queue
			{
				logger()->$type($message);
			}
			else // in command
			{
				if (empty(static::$consoleOutput))
					static::$consoleOutput = new OutputStyle(new ArgvInput(), new ConsoleOutput());

				$type = str_replace(['debug', 'info'], ['text', 'note'], $type);
				static::$consoleOutput->$type(sprintf('[%s] %s', date('Y-m-d H:i:s'), $message));
			}
		}
		else
		{
			logger()->$type($message);
		}
	}

	public static function error($message)
	{
		return static::write('error', $message);
	}

	public static function success($message)
	{
		return static::write('success', $message);
	}

	public static function info($message)
	{
		return static::write('info', $message);
	}

	public static function warning($message)
	{
		return static::write('warning', $message);
	}

	public static function debug($message)
	{
		return static::write('debug', $message);
	}

	public static function hex($message, array $options = [])
	{
		return static::debug(PHP_EOL.hex_dump($message, $options));
	}

}
