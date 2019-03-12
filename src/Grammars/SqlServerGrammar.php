<?php

namespace Staudenmeir\LaravelCte\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;

class SqlServerGrammar extends Base
{
    use CompilesExpressions;

    /**
     * Get the "recursive" keyword.
     *
     * @param  array  $expressions
     * @return string
     */
    protected function recursiveKeyword(array $expressions)
    {
        return '';
    }
}
