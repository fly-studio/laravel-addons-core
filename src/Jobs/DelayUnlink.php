<?php

namespace Addons\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DelayUnlink implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $path;
	protected $hash;
	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($path, $hash)
	{
		$this->path = $path;
		$this->hash = $hash;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		return ;

		if (is_link($this->path))
			unlink($this->path);
		else if (is_dir($this->path))
			rmdir_recursive($this->path);
		else
		{
			if ($this->hash == md5_file($this->path))
				unlink($this->path);
		}
	}
}
