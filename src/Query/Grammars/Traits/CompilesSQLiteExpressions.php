<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;
use Staudenmeir\LaravelCte\Query\Builder as CteBuilder;

trait CompilesSQLiteExpressions
{
    use CompilesExpressions {
        compileUpdate as compileUpdateTrait;
        compileDelete as compileDeleteTrait;
    }

    /**
     * Compile a single union statement.
     *
     * @param array{query: \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>, all: bool} $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        $builderClasses = [CteBuilder::class, 'Staudenmeir\EloquentEagerLimitXLaravelCte\Query\Builder'];

        for ($i = 6; $i <= 9; $i++) {
            if (isset($backtrace[$i]['class']) && in_array($backtrace[$i]['class'], $builderClasses)
                && $backtrace[$i]['function'] === 'withExpression') {
                $conjunction = $union['all'] ? ' union all ' : ' union ';

                return $conjunction.$union['query']->toSql();
            }
        }

        return parent::compileUnion($union);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, mixed> $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values)
    {
        if ($query->joins || isset($query->limit)) {
            return parent::compileUpdate($query, $values);
        }

        return $this->compileUpdateTrait($query, $values);
    }

    /**
     * Get the bindings for an update statement.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, mixed> $bindings
     * @param array<string, mixed> $values
     * @return list<mixed>
     */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values)
    {
        if ($query->joins || isset($query->limit)) {
            return parent::prepareBindingsForUpdate($bindings, $values);
        }

        return $this->prepareBindingsForUpdate($bindings, $values);
    }

    /** @inheritDoc */
    public function compileDelete(Builder $query)
    {
        if ($query->joins || isset($query->limit)) {
            return parent::compileDelete($query);
        }

        return $this->compileDeleteTrait($query);
    }
}
