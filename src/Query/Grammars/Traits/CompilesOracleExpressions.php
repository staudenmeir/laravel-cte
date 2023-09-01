<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

/**
 * @codeCoverageIgnore
 */
trait CompilesOracleExpressions
{
    use CompilesExpressions;

    /**
     * Get the "recursive" keyword.
     *
     * @param array $expressions
     * @return string
     */
    protected function recursiveKeyword(array $expressions)
    {
        return '';
    }
}
