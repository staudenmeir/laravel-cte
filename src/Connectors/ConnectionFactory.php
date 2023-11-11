<?php

namespace Staudenmeir\LaravelCte\Connectors;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory as Base;
use InvalidArgumentException;
use Staudenmeir\LaravelCte\Connections\MySqlConnection;
use Staudenmeir\LaravelCte\Connections\OracleConnection;
use Staudenmeir\LaravelCte\Connections\PostgresConnection;
use Staudenmeir\LaravelCte\Connections\SQLiteConnection;
use Staudenmeir\LaravelCte\Connections\SingleStoreConnection;
use Staudenmeir\LaravelCte\Connections\SqlServerConnection;

class ConnectionFactory extends Base
{
    /**
     * Create a new connection instance.
     *
     * @param string $driver
     * @param \PDO|\Closure $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return MySqlConnection|OracleConnection|PostgresConnection|SingleStoreConnection|SQLiteConnection|SqlServerConnection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection(string $driver, \PDO|\Closure $connection, string $database, string $prefix = '', array $config = []): SQLiteConnection|SingleStoreConnection|PostgresConnection|MySqlConnection|OracleConnection|SqlServerConnection
    {
        if ($driver !== 'singlestore' && $resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config); // @codeCoverageIgnore
        }

        return match ($driver) {
            'mysql' => new MySqlConnection($connection, $database, $prefix, $config),
            'pgsql' => new PostgresConnection($connection, $database, $prefix, $config),
            'sqlite' => new SQLiteConnection($connection, $database, $prefix, $config),
            'sqlsrv' => new SqlServerConnection($connection, $database, $prefix, $config),
            'oracle' => new OracleConnection($connection, $database, $prefix, $config), // @codeCoverageIgnore
            'singlestore' => new SingleStoreConnection($connection, $database, $prefix, $config),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]"), // @codeCoverageIgnore
        };
    }
}
