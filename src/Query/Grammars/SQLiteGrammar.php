<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;
use Staudenmeir\LaravelCte\Query\Builder;

class SQLiteGrammar extends Base
{
    use CompilesExpressions;

    /**
     * Compile a single union statement.
     *
     * @param array $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);

        if (($backtrace[6]['class'] === Builder::class && $backtrace[6]['function'] === 'withExpression')
            || ($backtrace[7]['class'] === Builder::class && $backtrace[7]['function'] === 'withExpression')) {
            $conjunction = $union['all'] ? ' union all ' : ' union ';

            return $conjunction.$union['query']->toSql();
        }

        return parent::compileUnion($union);
    }
}
