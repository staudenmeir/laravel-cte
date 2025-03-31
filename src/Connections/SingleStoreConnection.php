<?php

namespace Staudenmeir\LaravelCte\Connections;

use SingleStore\Laravel\Connect\SingleStoreConnection as Base;
use Staudenmeir\LaravelCte\Query\SingleStoreBuilder;

class SingleStoreConnection extends Base
{
    /** @inheritDoc */
    public function query()
    {
        return new SingleStoreBuilder($this);
    }
}
