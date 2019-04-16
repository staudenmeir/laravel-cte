<?php

namespace Staudenmeir\LaravelCte\Connectors;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory as Base;
use InvalidArgumentException;
use Staudenmeir\LaravelCte\Connections\MySqlConnection;
use Staudenmeir\LaravelCte\Connections\PostgresConnection;
use Staudenmeir\LaravelCte\Connections\SQLiteConnection;
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
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config); // @codeCoverageIgnore
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}]"); // @codeCoverageIgnore
    }
}
