<?php

namespace Addons\Core\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Redis\RedisManager;

class RWRedis {

	protected $redis = null;
	protected $stores = [];

	static $readOnlyKey = 'readonly';

	public function __construct()
	{
		$config = (array)config('database.redis');
		$driver = array_pull($config, 'client', 'predis');
		$readonly = array_pull($config, static::$readOnlyKey, []);

		foreach(array_except($config, ['options', 'clusters']) as $key => $value)
		{
			$newKey = $this->newKey($key);
			if (!array_key_exists($newKey, $config))
				$config[$newKey] = array_merge($value, $readonly);
		}

		if (!empty($config['clusters']))
		{
			foreach($config['clusters'] as $key => $value)
			{
				$newKey = $this->newKey($key);
				if (!array_key_exists($newKey, $config['clusters']))
					$config['clusters'][$newKey] = array_merge($value, $readonly);
			}
		}

		$this->redis = new RedisManager($driver, $config);
	}

	public function connection($connection = 'default')
	{
		if (empty($this->store[$connection]))
			$this->store[$connection] = new RedisStore($this->redis, config('cache.prefix'), $connection);

		return $this->store[$connection];
	}

	public function readOnlyConnection($connection = 'default')
	{
		$newKey = $this->newKey($connection);
		return $this->connection($newKey);
	}

	private function newKey($key)
	{
		return $key.':'.static::$readOnlyKey;
	}

}
