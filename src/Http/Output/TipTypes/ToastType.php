<?php

namespace Addons\Core\Http\Output\TipTypes;

use Addons\Core\Contracts\Http\Output\TipType;

class ToastType extends TipType {

	protected $type = 'toast';

	public function jsonSerialize()
	{
		return [
			'type' => $this->type,
			'timeout' => $this->getTimeout(),
		];
	}

}