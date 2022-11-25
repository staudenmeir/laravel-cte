<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;
use Illuminate\Support\Str;

class PostgresGrammar extends Base
{
    use CompilesExpressions {
        compileUpdate as compileUpdateTrait;
        compileDelete as compileDeleteTrait;
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
        if (isset($query->joins) || isset($query->limit)) {
            return parent::compileUpdate($query, $values);
        }

        return $this->compileUpdateTrait($query, $values);
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
        if (isset($query->joins) || isset($query->limit)) {
            return parent::prepareBindingsForUpdate($bindings, $values);
        }

        return $this->prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Compile an update from statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @return string
     */
    public function compileUpdateFrom(Builder $query, $values)
    {
        $compiled = parent::compileUpdateFrom($query, $values);

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
    public function prepareBindingsForUpdateFrom(array $bindings, array $values)
    {
        $values = array_merge($bindings['expressions'], $values);

        unset($bindings['expressions']);

        return parent::prepareBindingsForUpdateFrom($bindings, $values);
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return parent::compileDelete($query);
        }

        return $this->compileDeleteTrait($query);
    }
}
