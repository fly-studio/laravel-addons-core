<?php

namespace Addons\Core\Contracts\Http\Output;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

abstract class Action implements JsonSerializable, ArrayAccess, Arrayable
{

	abstract public function jsonSerialize();

	/**
	 * Convert the model instance to JSON.
	 *
	 * @param  int  $options
	 * @return string
	 *
	 * @throws \Illuminate\Database\Eloquent\JsonEncodingException
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->jsonSerialize(), $options);
	}

	public function offsetGet($key)
	{
		return $this->__get($key);
	}

	public function offsetExists($key)
	{
		return $this->__isset($key);
	}

	public function offsetSet($key, $value)
	{
		return null;
	}

	public function offsetUnset($key)
	{
		return null;
	}

	public function __get($key)
	{
		return property_exists($this, $key) ? $this->$key : null;
	}

	public function __isset($key)
	{
		return property_exists($this, $key);
	}


}
