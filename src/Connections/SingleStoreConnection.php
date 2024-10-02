<?php

namespace Staudenmeir\LaravelCte\Connections;

use SingleStore\Laravel\Connect\Connection;
use Staudenmeir\LaravelCte\Query\SingleStoreBuilder;

class SingleStoreConnection extends Connection
{
    /** @inheritDoc */
    public function query()
    {
        return new SingleStoreBuilder($this);
    }
}
