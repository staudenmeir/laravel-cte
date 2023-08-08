<?php

namespace Staudenmeir\LaravelCte\Connections;

use SingleStore\Laravel\Connect\Connection;
use Staudenmeir\LaravelCte\Query\SingleStoreBuilder;

class SingleStoreConnection extends Connection
{
    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new SingleStoreBuilder($this);
    }
}
