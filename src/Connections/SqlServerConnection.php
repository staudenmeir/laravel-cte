<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\SqlServerConnection as Base;
use Staudenmeir\LaravelCte\Grammars\SqlServerGrammar;

class SqlServerConnection extends Base
{
    use CreatesQueryBuilder;

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\SqlServerGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new SqlServerGrammar);
    }
}
