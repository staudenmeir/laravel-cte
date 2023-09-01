<?php

namespace Staudenmeir\LaravelCte\Connections;

use Staudenmeir\LaravelCte\Query\OracleBuilder;
use Yajra\Oci8\Oci8Connection;

/**
 * @codeCoverageIgnore
 */
class OracleConnection extends Oci8Connection
{
    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new OracleBuilder($this);
    }
}
