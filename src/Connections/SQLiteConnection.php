<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\SQLiteConnection as Base;
use Staudenmeir\LaravelCte\Grammars\SQLiteGrammar;

class SQLiteConnection extends Base
{
    use CreatesQueryBuilder;

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\SQLiteGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new SQLiteGrammar);
    }
}
