<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

trait CompilesPostgresExpressions
{
    use CompilesExpressions {
        compileUpdate as compileUpdateTrait;
        compileDelete as compileDeleteTrait;
    }

    /** @inheritDoc */
    public function compileUpdate(Builder $query, array $values)
    {
        if ($query->joins || isset($query->limit)) {
            return parent::compileUpdate($query, $values);
        }

        return $this->compileUpdateTrait($query, $values);
    }

    /** @inheritDoc */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values)
    {
        if ($query->joins || isset($query->limit)) {
            return parent::prepareBindingsForUpdate($bindings, $values);
        }

        return $this->prepareBindingsForUpdate($bindings, $values);
    }

    /** @inheritDoc */
    public function compileUpdateFrom(Builder $query, $values)
    {
        /** @var \Staudenmeir\LaravelCte\Query\Builder $query */

        $compiled = parent::compileUpdateFrom($query, $values);

        return (string) Str::of($compiled)
                           ->prepend($this->compileExpressions($query, $query->expressions), ' ')
                           ->trim();
    }

    /** @inheritDoc */
    public function prepareBindingsForUpdateFrom(array $bindings, array $values)
    {
        $values = array_merge($bindings['expressions'], $values);

        unset($bindings['expressions']);

        return parent::prepareBindingsForUpdateFrom($bindings, $values);
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
