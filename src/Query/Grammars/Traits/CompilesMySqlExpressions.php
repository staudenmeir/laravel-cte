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
     * @param array{name: string, query: string, columns: list<string|\Illuminate\Database\Query\Expression>|null,
     *        recursive: bool, materialized: bool|null,
     *        cycle: array{columns: list<string>, markColumn: string, pathColumn: string}|null} $expression
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
     * @param list<string|\Illuminate\Database\Query\Expression> $columns
     * @param string $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        /** @var \Staudenmeir\LaravelCte\Query\Builder $query */

        $insert = "insert into {$this->wrapTable($query->from)} ({$this->columnize($columns)}) ";

        return $insert.$this->compileExpressions($query, $query->expressions).' '.$sql;
    }
}
