<?php

namespace Staudenmeir\LaravelCte\Grammars;

use Illuminate\Database\Query\Builder;

trait CompilesExpressions
{
    /**
     * Create a new grammar instance.
     */
    public function __construct()
    {
        array_unshift($this->selectComponents, 'expressions');
    }

    /**
     * Compile the common table expressions.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileExpressions(Builder $query)
    {
        if (! $query->expressions) {
            return '';
        }

        $recursive = $this->recursiveKeyword($query->expressions);

        $statements = [];

        foreach ($query->expressions as $expression) {
            $columns = $expression['columns'] ? '('.$this->columnize($expression['columns']).') ' : '';

            $statements[] = $this->wrap($expression['name']).' '.$columns.'as ('.$expression['query'].')';
        }

        return 'with '.$recursive.implode($statements, ', ');
    }

    /**
     * Get the "recursive" keyword.
     *
     * @param  array  $expressions
     * @return string
     */
    protected function recursiveKeyword(array $expressions)
    {
        return collect($expressions)->where('recursive', true)->isNotEmpty() ? 'recursive ' : '';
    }

    /**
     * Compile an insert statement using a subquery into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @param  string  $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql)
    {
        return $this->compileExpressions($query).' '.parent::compileInsertUsing($query, $columns, $sql);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        return $this->compileExpressions($query).' '.parent::compileUpdate($query, $values);
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        return $this->compileExpressions($query).' '.parent::compileDelete($query);
    }
}
