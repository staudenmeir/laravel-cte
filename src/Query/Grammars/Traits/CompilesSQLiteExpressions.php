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

    /** @inheritDoc */
    protected function compileUnion(array $union)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        $builderClasses = [CteBuilder::class, 'Staudenmeir\EloquentEagerLimitXLaravelCte\Query\Builder'];

        for ($i = 6; $i <= 9; $i++) {
            if (in_array($backtrace[$i]['class'], $builderClasses) && $backtrace[$i]['function'] === 'withExpression') {
                $conjunction = $union['all'] ? ' union all ' : ' union ';

                return $conjunction.$union['query']->toSql();
            }
        }

        return parent::compileUnion($union);
    }

    /** @inheritDoc */
    public function compileUpdate(Builder $query, array $values)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return parent::compileUpdate($query, $values);
        }

        return $this->compileUpdateTrait($query, $values);
    }

    /** @inheritDoc */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return parent::prepareBindingsForUpdate($bindings, $values);
        }

        return $this->prepareBindingsForUpdate($bindings, $values);
    }

    /** @inheritDoc */
    public function compileDelete(Builder $query)
    {
        if (isset($query->joins) || isset($query->limit)) {
            return parent::compileDelete($query);
        }

        return $this->compileDeleteTrait($query);
    }
}
