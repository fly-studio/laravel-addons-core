<?php

namespace Addons\Core\Http\Output\TipTypes;

use Addons\Core\Contracts\Http\Output\TipType;

class BackType extends TipType {

	protected $type = 'back';

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function toArray()
	{
		return [
			'type' => $this->type,
			'timeout' => $this->getTimeout(),
		];
	}

}