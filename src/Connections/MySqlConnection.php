<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\MySqlConnection as Base;
use Staudenmeir\LaravelCte\Grammars\MySqlGrammar;

class MySqlConnection extends Base
{
    use CreatesQueryBuilder;

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new MySqlGrammar);
    }
}
