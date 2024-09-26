<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Builder;

interface ExpressionGrammar
{
    /**
     * Compile an insert statement using a subquery into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param list<string|\Illuminate\Database\Query\Expression> $columns
     * @param string $sql
     * @return string
     */
    public function compileInsertUsing(Builder $query, array $columns, string $sql);

    /**
     * Compile an update statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, mixed> $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values);

    /**
     * Prepare the bindings for an update statement.
     *
     * @param array{expressions: list<mixed>, select: list<mixed>, from: list<mixed>, join: list<mixed>,
     *      where: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>,
     *      unionOrder: list<mixed>} $bindings
     * @param array<string, mixed> $values
     * @return list<mixed>
     */
    public function prepareBindingsForUpdate(array $bindings, array $values);

    /**
     * Get the bindings for an update statement.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array{expressions: list<mixed>, select: list<mixed>, from: list<mixed>, join: list<mixed>,
     *      where: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>,
     *      unionOrder: list<mixed>} $bindings
     * @param array<string, mixed> $values
     * @return list<mixed>
     */
    public function getBindingsForUpdate(Builder $query, array $bindings, array $values);
}
