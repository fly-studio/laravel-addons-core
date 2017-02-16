<?php

namespace Addons\Core\Http\Output\TipTypes;

use Addons\Core\Contracts\Http\Output\TipType;

class RefreshType extends TipType {

	protected $type = 'refresh';

	public function jsonSerialize()
	{
		return [
			'type' => $this->type,
			'timeout' => $this->getTimeout(),
		];
	}

}