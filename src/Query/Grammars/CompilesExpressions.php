<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Staudenmeir\LaravelCte\Query\Builder as CteBuilder;

trait CompilesExpressions
{
    /**
     * Create a new grammar instance.
     */
    public function __construct()
    {
        array_unshift($this->selectComponents, 'expressions');

        $this->selectComponents[] = 'recursionLimit';
    }

    /**
     * Compile the common table expressions.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $expressions
     * @return string
     */
    public function compileExpressions(Builder $query, array $expressions)
    {
        if (!$expressions) {
            return '';
        }

        $recursive = $this->recursiveKeyword($expressions);

        $statements = [];

        foreach ($expressions as $expression) {
            $columns = $expression['columns'] ? '('.$this->columnize($expression['columns']).') ' : '';

            $materialized = !is_null($expression['materialized'])
                ? ($expression['materialized'] ? 'materialized ' : 'not materialized ')
                : '';

            $statements[] = $this->wrapTable($expression['name']).' '.$columns.'as '.$materialized.'('.$expression['query'].')';
        }

        return 'with '.$recursive.implode(', ', $statements);
    }

    /**
     * Get the "recursive" keyword.
     *
     * @param array $expressions
     * @return string
     */
    protected function recursiveKeyword(array $expressions)
    {
        return collect($expressions)->where('recursive', true)->isNotEmpty() ? 'recursive ' : '';
    }

    /**
     * Compile the recursion limit.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int|null $recursionLimit
     * @return string
     */
    public function compileRecursionLimit(Builder $query, $recursionLimit)
    {
        if (is_null($recursionLimit)) {
            return '';
        }

        return 'option (maxrecursion '.(int) $recursionLimit.')';
    }

    /**
     * Compile a select query into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        $sql = parent::compileSelect($query);

        if ($query instanceof CteBuilder) {
            if ($query->unionExpressions) {
                $sql = $this->compileExpressions($query, $query->unionExpressions) . " $sql";
            }

            if (!is_null($query->unionRecursionLimit)) {
                $sql .= ' ' . $this->compileRecursionLimit($query, $query->unionRecursionLimit);
            }
        }

        return $sql;
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
        $expressions = $this->compileExpressions($query, $query->expressions);

        $recursionLimit = $this->compileRecursionLimit($query, $query->recursionLimit);

        $compiled = parent::compileInsertUsing($query, $columns, $sql);

        return (string) Str::of($compiled)
            ->prepend($expressions, ' ')
            ->append(' ', $recursionLimit)
            ->trim();
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values)
    {
        $compiled = parent::compileUpdate($query, $values);

        return (string) Str::of($compiled)
            ->prepend($this->compileExpressions($query, $query->expressions), ' ')
            ->trim();
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param array $bindings
     * @param array $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $values = array_merge($bindings['expressions'], $values);

        unset($bindings['expressions']);

        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Get the bindings for an update statement.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $bindings
     * @param array $values
     * @return array
     */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values)
    {
        return $this->prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $compiled = parent::compileDelete($query);

        return (string) Str::of($compiled)
            ->prepend($this->compileExpressions($query, $query->expressions), ' ')
            ->trim();
    }
}
