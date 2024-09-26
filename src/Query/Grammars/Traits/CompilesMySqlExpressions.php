<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;

trait CompilesMySqlExpressions
{
    use CompilesExpressions;

    /** @inheritDoc */
    public function compileCycle(Builder $query, array $expression)
    {
        if (!$expression['cycle']) {
            return '';
        }

        $columns = $this->columnize($expression['cycle']['columns']);

        return " cycle $columns restrict";
    }

    /** @inheritDoc */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        /** @var \Staudenmeir\LaravelCte\Query\Builder $query */

        $insert = "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) ";

        return $insert.$this->compileExpressions($query, $query->expressions).' '.$sql;
    }
}
