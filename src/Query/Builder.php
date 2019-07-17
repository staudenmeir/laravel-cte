<?php

namespace Staudenmeir\LaravelCte\Query;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as Base;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use InvalidArgumentException;
use RuntimeException;
use Staudenmeir\LaravelCte\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SqlServerGrammar;

class Builder extends Base
{
    /**
     * The common table expressions.
     *
     * @var array
     */
    public $expressions = [];

    /**
     * The recursion limit.
     *
     * @var int
     */
    public $recursionLimit;

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
                return new MySqlGrammar;
            case 'pgsql':
                return new PostgresGrammar;
            case 'sqlite':
                return new SQLiteGrammar;
            case 'sqlsrv':
                return new SqlServerGrammar;
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
     * @return $this
     */
    public function withExpression($name, $query, array $columns = null, $recursive = false)
    {
        list($query, $bindings) = $this->createSub($query);

        $this->expressions[] = compact('name', 'query', 'columns', 'recursive');

        $this->addBinding($bindings, 'expressions');

        return $this;
    }

    /**
     * Add a recursive common table expression to the query.
     *
     * @param string $name
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param array|null $columns
     * @return $this
     */
    public function withRecursiveExpression($name, $query, $columns = null)
    {
        return $this->withExpression($name, $query, $columns, true);
    }

    /**
     * Set the recursion limit of the query.
     *
     * @param int $value
     * @return $this
     */
    public function recursionLimit($value)
    {
        $this->recursionLimit = $value;

        return $this;
    }

    /**
     * Insert new records into the table using a subquery.
     *
     * @param array $columns
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @return bool
     */
    public function insertUsing(array $columns, $query)
    {
        list($sql, $bindings) = $this->createSub($query);

        $bindings = array_merge($this->bindings['expressions'], $bindings);

        return $this->connection->insert(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }

    /**
     * Create a subquery and parse it.
     *
     * @param \Closure|\Illuminate\Database\Query\Builder|string $query
     * @return array
     */
    protected function createSub($query)
    {
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Create a new query instance for a sub-query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param mixed $query
     * @return array
     */
    protected function parseSub($query)
    {
        if ($query instanceof self || $query instanceof EloquentBuilder) {
            return [$query->toSql(), $query->getBindings()];
        } elseif (is_string($query)) {
            return [$query, []];
        } else {
            throw new InvalidArgumentException; // @codeCoverageIgnore
        }
    }
}
