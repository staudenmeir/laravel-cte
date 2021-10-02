<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;

class MySqlGrammar extends Base
{
    use CompilesExpressions;

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
