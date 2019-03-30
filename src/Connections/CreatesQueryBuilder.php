<?php

namespace Staudenmeir\LaravelCte\Connections;

use Staudenmeir\LaravelCte\Query\Builder;

trait CreatesQueryBuilder
{
    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new Builder($this);
    }
}
