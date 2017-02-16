<?php

namespace Addons\Core\Contracts\Http\Output;

abstract class TipType implements \JsonSerializable
{

	protected $timeout = null;
	protected $type = null;

	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}

	public function getTimeout()
	{
		return is_null($this->timeout) ? config('output.tipTypes.'.$this->type.'.timeout') : $this->timeout;
	}

	abstract public function jsonSerialize();
}