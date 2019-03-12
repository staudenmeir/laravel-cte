<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\PostgresConnection as Base;
use Staudenmeir\LaravelCte\Grammars\PostgresGrammar;

class PostgresConnection extends Base
{
    use CreatesQueryBuilder;

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new PostgresGrammar);
    }
}
