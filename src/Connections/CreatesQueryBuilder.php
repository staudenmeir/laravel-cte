<?php

namespace Staudenmeir\LaravelCte\Connections;

use Staudenmeir\LaravelCte\Query\Builder;

trait CreatesQueryBuilder
{
    /** @inheritDoc */
    public function query()
    {
        return new Builder($this);
    }
}
