<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;
use Staudenmeir\LaravelCte\Query\Builder as CteBuilder;

class SQLiteGrammar extends Base
{
    use CompilesExpressions {
        compileUpdate as compileUpdateTrait;
        compileDelete as compileDeleteTrait;
    }

    /**
     * Compile a single union statement.
     *
     * @param array $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9);

        if (($backtrace[6]['class'] === CteBuilder::class && $backtrace[6]['function'] === 'withExpression')
            || ($backtrace[7]['class'] === CteBuilder::class && $backtrace[7]['function'] === 'withExpression')
            || ($backtrace[8]['class'] === CteBuilder::class && $backtrace[8]['function'] === 'withExpression')) {
            $conjunction = $union['all'] ? ' union all ' : ' union ';

            return $conjunction.$union['query']->toSql();
        }

        return parent::compileUnion($union);
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
