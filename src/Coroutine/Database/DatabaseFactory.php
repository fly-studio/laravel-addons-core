<?php

namespace Addons\Core\Database;

use PDO;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseFactory {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * The database connection factory instance.
	 *
	 * @var \Illuminate\Database\Connectors\ConnectionFactory
	 */
	protected $factory;


	/**
	 * Create a new database manager instance.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @param  \Illuminate\Database\Connectors\ConnectionFactory  $factory
	 * @return void
	 */
	public function __construct($app, ConnectionFactory $factory)
	{
		$this->app = $app;
		$this->factory = $factory;

	}

	/**
	 * Get a database connection instance.
	 *
	 * @param  string|null  $name
	 * @return \Illuminate\Database\Connection
	 */
	public function make(string $name = null)
	{
		[$database, $type] = $this->parseConnectionName($name);

		$name = $name ?: $database;

		// If we haven't created this connection, we'll create it based on the config
		// provided in the application. Once we've created the connections we will
		// set the "fetch mode" for PDO which determines the query return types.

		return $this->configure(
			$this->makeConnection($database), $type
		);
	}

	/**
	 * Parse the connection into an array of the name and read / write type.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function parseConnectionName($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		return Str::endsWith($name, ['::read', '::write'])
							? explode('::', $name, 2) : [$name, null];
	}

	/**
	 * Make the database connection instance.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Database\Connection
	 */
	protected function makeConnection($name)
	{
		$config = $this->configuration($name);
		$config['pool'] = true;

		return $this->factory->make($config, $name);
	}

	/**
	 * Get the configuration for a connection.
	 *
	 * @param  string  $name
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function configuration($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		// To get the database connection configuration, we will just pull each of the
		// connection configurations and get the configurations for the given name.
		// If the configuration doesn't exist, we'll throw an exception and bail.
		$connections = $this->app['config']['database.connections'];

		if (is_null($config = Arr::get($connections, $name))) {
			throw new InvalidArgumentException("Database [{$name}] not configured.");
		}

		return (new ConfigurationUrlParser)
					->parseConfiguration($config);
	}

	/**
	 * Prepare the database connection instance.
	 *
	 * @param  \Illuminate\Database\Connection  $connection
	 * @param  string  $type
	 * @return \Illuminate\Database\Connection
	 */
	protected function configure(Connection $connection, $type)
	{
		$connection = $this->setPdoForType($connection, $type);

		// First we'll set the fetch mode and a few other dependencies of the database
		// connection. This method basically just configures and prepares it to get
		// used by the application. Once we're finished we'll return it back out.
		if ($this->app->bound('events')) {
			$connection->setEventDispatcher($this->app['events']);
		}

		// Here we'll set a reconnector callback. This reconnector can be any callable
		// so we will set a Closure to reconnect from this manager with the name of
		// the connection, which will allow us to reconnect from the connections.
		$connection->setReconnector(function ($connection) {
			return $this->reconnect($connection);
		});

		return $connection;
	}

	/**
	 * Prepare the read / write mode for database connection instance.
	 *
	 * @param  \Illuminate\Database\Connection  $connection
	 * @param  string|null  $type
	 * @return \Illuminate\Database\Connection
	 */
	protected function setPdoForType(Connection $connection, $type = null)
	{
		if ($type === 'read') {
			$connection->setPdo($connection->getReadPdo());
		} elseif ($type === 'write') {
			$connection->setReadPdo($connection->getPdo());
		}

		return $connection;
	}

	/**
	 * Disconnect from the given database.
	 *
	 * @param  string|null  $name
	 * @return void
	 */
	public function disconnect(Connection $connection)
	{
		$connection->disconnect();
	}

	public function isConnected(Connection $connection)
	{
		$pdo = $connection->getPdo();
		return !empty($pdo);
	}

	/**
	 * Reconnect to the given database.
	 *
	 * @param  string|null  $name
	 * @return \Illuminate\Database\Connection
	 */
	public function reconnect(Connection $connection)
	{
		$this->disconnect($connection);

		$this->refreshPdoConnections($connection);
	}

	/**
	 * Refresh the PDO connections on a given connection.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Database\Connection
	 */
	protected function refreshPdoConnections(Connection $connection)
	{
		$fresh = $this->makeConnection($connection->getName());

		return $connection
						->setPdo($fresh->getPdo())
						->setReadPdo($fresh->getReadPdo());
	}

	/**
	 * Get the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection()
	{
		return app('db')->getDefaultConnection();
	}
}
