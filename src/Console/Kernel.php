<?php

namespace Addons\Core\Console;

use Illuminate\Foundation\Console\Kernel as BaseKernel;

class Kernel extends BaseKernel {

	/**
	 * Run an Artisan console command by name.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return int
	 */
	public function run($commandline)
	{
		$this->bootstrap();

		$artisan = $this->getArtisan();
		$artisan->setCatchExceptions(false);
		$artisan->run($input = new \Symfony\Component\Console\Input\StringInput(trim(str_replace('php artisan', '', $commandline))), $out = new \Symfony\Component\Console\Output\BufferedOutput);
		$artisan->setCatchExceptions(true);
		return $out;
	}

	public function setCommands($commands)
	{
		$this->commands = $commands;
	}

	public function getCommands()
	{
		return $commands;
	}

	public function addCommand($command)
	{
		$this->commands[] = $command;
	}
}
