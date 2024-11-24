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

    /**
     * Compile an update from statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param list<mixed> $values
     * @return string
     */
    public function compileUpdateFrom(Builder $query, $values)
    {
        /** @var \Staudenmeir\LaravelCte\Query\Builder $query */

        $compiled = parent::compileUpdateFrom($query, $values);

        return (string) Str::of($compiled)
                           ->prepend($this->compileExpressions($query, $query->expressions), ' ')
                           ->trim();
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param array{expressions: list<mixed>, select: list<mixed>, from: list<mixed>, join: list<mixed>,
     *      where: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>,
     *      unionOrder: list<mixed>} $bindings
     * @param list<mixed> $values
     * @return array<int, mixed>
     */
    public function prepareBindingsForUpdateFrom(array $bindings, array $values)
    {
        $values = array_merge($bindings['expressions'], $values);

        unset($bindings['expressions']);

        /** @var array<int, mixed> $bindings */
        $bindings = parent::prepareBindingsForUpdateFrom($bindings, $values);

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
