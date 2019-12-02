<?php

namespace Addons\Core\Http\Output\Actions;

use Addons\Core\Http\Output\ActionFactory;
use Addons\Core\Contracts\Http\Output\Action;

class RedirectAction extends Action {

	protected $timeout;
	protected $url;

	public function __construct(int $timeout = 1500, string $url)
	{
		$this->timeout = $timeout;
		$this->url = app('router')->has($url) ? route($url) : url($url);
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function toArray()
	{
		return [
			ActionFactory::REDIRECT, $this->timeout, $this->url,
		];
	}


}
