<?php

namespace Staudenmeir\LaravelCte\Eloquent;

use Staudenmeir\LaravelCte\Query\Builder;
use Staudenmeir\LaravelCte\Query\OracleBuilder;

trait QueriesExpressions
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return match ($connection->getDriverName()) {
            'oracle' => new OracleBuilder($connection),
            default => new Builder($connection),
        };
    }
}
