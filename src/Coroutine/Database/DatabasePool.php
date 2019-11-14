<?php

namespace Addons\Core\Coroutine\Database;

use Co, Exception;
use RuntimeException;
use Swoole\Coroutine\Client;
use Swoole\Coroutine\Channel;
use Addons\Server\Structs\Pool;
use Addons\Func\Console\ConsoleLog;
use Addons\Server\Exceptions\ConnectionException;

// 一个Illuminate\Database\Connection连接池
// 这个类只能运行在go(function(){ .... });中
// 注意所有的实现都是协程的，发送和接收不会阻塞其它任务，根据测试异步发送效率要比同步高很多。
class DatabasePool extends Pool {

	protected $connectionName;
	protected $databaseFactory;

	public function __construct(string $connectionName = null, int $poolCount = 10)
	{
		parent::__construct($poolCount);

		$this->connectionName = $connectionName;

		$app = app();

		$this->databaseFactory = new DatabaseFactory($app, $app['db.factory']);

	}

	protected function doNewConnect()
	{
		return $this->databaseFactory->make($this->connectionName);
	}

	protected function isConnected($connection)
	{
		return $this->databaseFactory->isConnected($connection);
	}

	protected function doClose($connection, bool $force)
	{
		$this->databaseFactory->disconnect($connection);
	}

}
