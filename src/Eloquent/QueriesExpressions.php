<?php

namespace Staudenmeir\LaravelCte\Eloquent;

use Staudenmeir\LaravelCte\Query\Builder;

trait QueriesExpressions
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        return new Builder($this->getConnection());
    }
}
