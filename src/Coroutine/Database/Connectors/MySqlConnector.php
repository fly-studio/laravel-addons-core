<?php

namespace Addons\Core\Coroutine\Database\Connectors;

use Throwable;
use Illuminate\Support\Str;
use Addons\Core\Coroutine\Database\PDOConnection;
use Illuminate\Database\Connectors\MySqlConnector as BaseMySqlConnector;

class MySqlConnector extends BaseMySqlConnector
{
	protected function createPdoConnection($dsn, $username, $password, $options)
	{
		return new PDOConnection(...func_get_args());
	}

	protected function tryAgainIfCausedByLostConnection(Throwable $e, $dsn, $username, $password, $options)
	{
		if ($this->causedByLostConnection($e) || Str::contains($e->getMessage(), 'is closed'))
			return $this->createPdoConnection($dsn, $username, $password, $options);

		throw $e;
	}
}
