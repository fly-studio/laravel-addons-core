<?php

namespace Addons\Core\Cache;

use Illuminate\Support\Arr;
use Illuminate\Cache\RedisStore;
use Illuminate\Redis\RedisManager;

class RedisHashTable {

	protected $redis;

	public function __construct(string $connection = null)
	{
		$this->redis = app('redis')->connection($connection);
	}

	private function serialize($v)
	{
		return serialize($v);
	}

	private function serializeArray(array $data)
	{
		return array_map(function($v){
			return $this->serialize($v);
		}, $data);
	}

	private function unserialize(string $v)
	{
		return !empty($v) ? unserialize($v) : null;
	}

	private function unserializeArray(array $data)
	{
		return array_map(function($v) {
			return $this->unserialize($v);
		}, $data);
	}

	public function hset(string $key, string $field, $value)
	{
		return $this->redis->hset($key, $field, $this->serialize($value));
	}

	public function hget(string $key, string $field)
	{
		return $this->unserialize($this->redis->hget($key, $field));
	}

	public function hgetall(string $key)
	{
		return $this->unserializeArray($this->redis->hgetall($key));
	}

	public function hmget(string $key, array $fields)
	{
		return array_combine($fields, $this->unserializeArray($this->redis->hmget($key, $fields)));
	}

	public function hmset(string $key, array $data)
	{
		return $this->redis->hmset($key, $this->serializeArray($data));
	}

	public function __call($method, $parameters)
	{
		return $this->redis->$method(...$parameters);
	}
}
