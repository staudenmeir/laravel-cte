<?php

namespace Staudenmeir\LaravelCte\Connections;

use SingleStore\Laravel\Connect\Connection as Base;
use Staudenmeir\LaravelCte\Query\SingleStoreBuilder;

class SingleStoreConnection extends Base
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
