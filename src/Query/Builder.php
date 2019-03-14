<?php

namespace Staudenmeir\LaravelCte\Query;

use Illuminate\Database\Query\Builder as Base;

class Builder extends Base
{
    /**
     * The common table expressions.
     *
     * @var array
     */
    public $expressions = [];

    /**
     * Create a new query builder instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->bindings = ['expressions' => []] + $this->bindings;
    }

    /**
     * Add a common table expression to the query.
     *
     * @param  string  $name
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  array|null  $columns
     * @param  bool  $recursive
     * @return $this
     */
    public function withExpression($name, $query, array $columns = null, $recursive = false)
    {
        [$query, $bindings] = $this->createSub($query);

        $this->expressions[] = compact('name', 'query', 'columns', 'recursive');

        $this->addBinding($bindings, 'expressions');

        return $this;
    }

    /**
     * Add a recursive common table expression to the query.
     *
     * @param  string  $name
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  array|null  $columns
     * @return $this
     */
    public function withRecursiveExpression($name, $query, $columns = null)
    {
        return $this->withExpression($name, $query, $columns, true);
    }

    /**
     * Insert new records into the table using a subquery.
     *
     * @param  array  $columns
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @return bool
     */
    public function insertUsing(array $columns, $query)
    {
        [$sql, $bindings] = $this->createSub($query);

        $bindings = array_merge($this->bindings['expressions'], $bindings);

        return $this->connection->insert(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }
}
