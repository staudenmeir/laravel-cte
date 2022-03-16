<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;

trait CompilesMySqlExpressions
{
    use CompilesExpressions;

    /**
     * Compile the cycle detection.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $expression
     * @return string
     */
    public function compileCycle(Builder $query, array $expression)
    {
        if (!$expression['cycle']) {
            return '';
        }

        $columns = $this->columnize($expression['cycle']['columns']);

        return " cycle $columns restrict";
    }

    /**
     * Compile an insert statement using a subquery into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $columns
     * @param string $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        $insert = "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) ";

        return $insert.$this->compileExpressions($query, $query->expressions).' '.$sql;
    }
}
