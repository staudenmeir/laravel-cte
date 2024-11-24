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
     * @param array{expressions: list<mixed>, select: list<mixed>, from: list<mixed>, join: list<mixed>,
     *      where: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>,
     *      unionOrder: list<mixed>} $bindings
     * @param array<string, mixed> $values
     * @return array<int, mixed>
     */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values)
    {
        if ($query->joins || isset($query->limit)) {
            /** @var array<int, mixed> $bindings */
            $bindings = parent::prepareBindingsForUpdate($bindings, $values);

            return $bindings;
        }

        /** @var array<int, mixed> $bindings */
        $bindings = $this->prepareBindingsForUpdate($bindings, $values);

        return $bindings;
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
