<?php

namespace Staudenmeir\LaravelCte\Query\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use RuntimeException;
use Staudenmeir\LaravelCte\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SqlServerGrammar;

trait BuildsExpressionQueries
{
    /**
     * The common table expressions.
     *
     * @var array
     */
    public $expressions = [];

    /**
     * The common table expressions for union queries.
     *
     * @var array
     */
    public $unionExpressions = [];

    /**
     * The recursion limit.
     *
     * @var int
     */
    public $recursionLimit;

    /**
     * The recursion limit for union queries.
     *
     * @var int
     */
    public $unionRecursionLimit;

    /**
     * Create a new query builder instance.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param \Illuminate\Database\Query\Grammars\Grammar|null $grammar
     * @param \Illuminate\Database\Query\Processors\Processor|null $processor
     * @return void
     */
    public function __construct(Connection $connection, Grammar $grammar = null, Processor $processor = null)
    {
        $grammar = $grammar ?: $connection->withTablePrefix($this->getQueryGrammar($connection));
        $processor = $processor ?: $connection->getPostProcessor();

        parent::__construct($connection, $grammar, $processor);

        $this->bindings = ['expressions' => []] + $this->bindings;
    }

    /**
     * Get the query grammar.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getQueryGrammar(Connection $connection)
    {
        $driver = $connection->getDriverName();

        switch ($driver) {
            case 'mysql':
                return new MySqlGrammar();
            case 'pgsql':
                return new PostgresGrammar();
            case 'sqlite':
                return new SQLiteGrammar();
            case 'sqlsrv':
                return new SqlServerGrammar();
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }

    /**
     * Add a common table expression to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|null $columns
     * @param bool $recursive
     * @param bool|null $materialized
     * @param array|null $cycle
     * @return $this
     */
    public function withExpression($name, $query, array $columns = null, $recursive = false, $materialized = null, array $cycle = null)
    {
        [$query, $bindings] = $this->createSub($query);

        $this->{$this->unions ? 'unionExpressions' : 'expressions'}[] = compact('name', 'query', 'columns', 'recursive', 'materialized', 'cycle');

        $this->addBinding($bindings, 'expressions');

        return $this;
    }

    /**
     * Add a recursive common table expression to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|null $columns
     * @param array|null $cycle
     * @return $this
     */
    public function withRecursiveExpression($name, $query, $columns = null, array $cycle = null)
    {
        return $this->withExpression($name, $query, $columns, true, null, $cycle);
    }

    /**
     * Add a recursive common table expression with cycle detection to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|string $cycleColumns
     * @param string $markColumn
     * @param string $pathColumn
     * @param array|null $columns
     * @return $this
     */
    public function withRecursiveExpressionAndCycleDetection($name, $query, $cycleColumns, $markColumn = 'is_cycle', $pathColumn = 'path', $columns = null)
    {
        $cycleColumns = (array) $cycleColumns;

        $cycle = [
            'columns' => $cycleColumns
        ] + compact('markColumn', 'pathColumn');

        return $this->withRecursiveExpression($name, $query, $columns, $cycle);
    }

    /**
     * Add a materialized common table expression to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|null $columns
     * @return $this
     */
    public function withMaterializedExpression($name, $query, $columns = null)
    {
        return $this->withExpression($name, $query, $columns, false, true);
    }

    /**
     * Add a non-materialized common table expression to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|null $columns
     * @return $this
     */
    public function withNonMaterializedExpression($name, $query, $columns = null)
    {
        return $this->withExpression($name, $query, $columns, false, false);
    }

    /**
     * Set the recursion limit of the query.
     *
     * @param int $value
     * @return $this
     */
    public function recursionLimit($value)
    {
        $this->{$this->unions ? 'unionRecursionLimit' : 'recursionLimit'} = $value;

        return $this;
    }

    /**
     * Insert new records into the table using a subquery.
     *
     * @param array $columns
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @return int
     */
    public function insertUsing(array $columns, $query)
    {
        $this->applyBeforeQueryCallbacks();

        [$sql, $bindings] = $this->createSub($query);

        $bindings = array_merge($this->bindings['expressions'], $bindings);

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }

    /**
     * Update records in the database.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        $this->applyBeforeQueryCallbacks();

        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->grammar->getBindingsForUpdate($this, $this->bindings, $values)
        ));
    }

    /**
     * Update records in a PostgreSQL database using the update from syntax.
     *
     * @param array $values
     * @return int
     */
    public function updateFrom(array $values)
    {
        $this->applyBeforeQueryCallbacks();

        $sql = $this->grammar->compileUpdateFrom($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdateFrom($this->bindings, $values)
        ));
    }
}
