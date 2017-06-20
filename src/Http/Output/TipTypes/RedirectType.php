<?php

namespace Addons\Core\Http\Output\TipTypes;

use Addons\Core\Contracts\Http\Output\TipType;

class RedirectType extends TipType {

	protected $type = 'redirect';
	protected $url = null;

	public function setUrl($url)
	{
		$this->url = url($url);
		return $this;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
			'type' => $this->type,
			'url' => $this->getUrl(),
			'timeout' => $this->getTimeout(),
		];
    }


}