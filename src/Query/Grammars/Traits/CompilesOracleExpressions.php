<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;

trait CompilesOracleExpressions
{
    use CompilesExpressions;

    /** @inheritDoc */
    protected function recursiveKeyword(array $expressions)
    {
        return '';
    }

    /** @inheritDoc */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        /** @var \Staudenmeir\LaravelCte\Query\OracleBuilder $query */

        $insert = "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) ";

        return "$insert{$this->compileExpressions($query, $query->expressions)} $sql";
    }
}
