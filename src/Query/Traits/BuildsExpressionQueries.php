<?php

namespace Staudenmeir\LaravelCte\Query\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use RuntimeException;
use Staudenmeir\LaravelCte\Query\Grammars\FirebirdGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\MariaDbGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\OracleGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SingleStoreGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\SqlServerGrammar;

trait BuildsExpressionQueries
{
    /**
     * The Laravel native query grammars.
     *
     * @var list<class-string<\Illuminate\Database\Query\Grammars\Grammar>>
     */
    public const NATIVE_GRAMMARS = [
        \Illuminate\Database\Query\Grammars\MySqlGrammar::class,
        \Illuminate\Database\Query\Grammars\MariaDbGrammar::class,
        \Illuminate\Database\Query\Grammars\PostgresGrammar::class,
        \Illuminate\Database\Query\Grammars\SQLiteGrammar::class,
        \Illuminate\Database\Query\Grammars\SqlServerGrammar::class,
    ];

    /**
     * The common table expressions.
     *
     * @var list<array{name: string, query: string, columns: list<string|\Illuminate\Database\Query\Expression<*>>|null,
     *       recursive: bool, materialized: bool|null,
     *       cycle: array{columns: list<string>, markColumn: string, pathColumn: string}|null}>
     */
    public $expressions = [];

    /**
     * The common table expressions for union queries.
     *
     * @var list<array{name: string, query: string, columns: list<string|\Illuminate\Database\Query\Expression<*>>|null,
     *        recursive: bool, materialized: bool|null,
     *        cycle: array{columns: list<string>, markColumn: string, pathColumn: string}|null}>
     */
    public $unionExpressions = [];

    /**
     * The recursion limit.
     *
     * @var int|null
     */
    public $recursionLimit;

    /**
     * The recursion limit for union queries.
     *
     * @var int|null
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
    public function __construct(Connection $connection, ?Grammar $grammar = null, ?Processor $processor = null)
    {
        // Override the provided grammar if it is null or a native grammar
        if (is_null($grammar) || in_array($grammar::class, self::NATIVE_GRAMMARS)) {
            $grammar = $this->getQueryGrammar($connection);
        }

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

        return match ($driver) {
            'mysql' => new MySqlGrammar($connection),
            'mariadb' => new MariaDbGrammar($connection),
            'pgsql' => new PostgresGrammar($connection),
            'sqlite' => new SQLiteGrammar($connection),
            'sqlsrv' => new SqlServerGrammar($connection),
            'oracle' => new OracleGrammar($connection),
            'singlestore' => new SingleStoreGrammar(
                connection: $connection,
                ignoreOrderByInDeletes: $connection->getConfig('ignore_order_by_in_deletes'),
                ignoreOrderByInUpdates: $connection->getConfig('ignore_order_by_in_updates')
            ),
            'firebird' => new FirebirdGrammar($connection),
            default => throw new RuntimeException('This database is not supported.'), // @codeCoverageIgnore
        };
    }

    /**
     * Add a common table expression to the query.
     *
     * @param string $name
     * @param string|\Closure|\Illuminate\Database\Query\Builder $query
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|null $columns
     * @param bool $recursive
     * @param bool|null $materialized
     * @param array{columns: list<string>, markColumn: string, pathColumn: string}|null $cycle
     * @return $this
     */
    public function withExpression($name, $query, ?array $columns = null, $recursive = false, $materialized = null, ?array $cycle = null)
    {
        /** @var string $query */
        [$query, $bindings] = $this->createSub($query);

        $expression = compact('name', 'query', 'columns', 'recursive', 'materialized', 'cycle');

        if ($this->unions) {
            $this->unionExpressions[] = $expression;
        } else {
            $this->expressions[] = $expression;
        }

        $this->addBinding($bindings, 'expressions');

        return $this;
    }

    /**
     * Add a recursive common table expression to the query.
     *
     * @param string $name
     * @param string|\Closure|\Illuminate\Database\Query\Builder $query
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|null $columns
     * @param array{columns: list<string>, markColumn: string, pathColumn: string}|null $cycle
     * @return $this
     */
    public function withRecursiveExpression($name, $query, $columns = null, ?array $cycle = null)
    {
        return $this->withExpression($name, $query, $columns, true, null, $cycle);
    }

    /**
     * Add a recursive common table expression with cycle detection to the query.
     *
     * @param string $name
     * @param string|\Closure|\Illuminate\Database\Query\Builder $query
     * @param list<string>|string $cycleColumns
     * @param string $markColumn
     * @param string $pathColumn
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|null $columns
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
     * @param string|\Closure|\Illuminate\Database\Query\Builder $query
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|null $columns
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
     * @param string|\Closure|\Illuminate\Database\Query\Builder $query
     * @param list<string|\Illuminate\Database\Query\Expression<*>>|null $columns
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
     * @param list<string|\Illuminate\Database\Query\Expression<*>> $columns
     * @param string|\Closure|\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder $query
     * @return int
     */
    public function insertUsing(array $columns, $query)
    {
        $this->applyBeforeQueryCallbacks();

        /** @var array<int, mixed> $expressionBindings */
        $expressionBindings = $this->bindings['expressions'];

        /** @var string $sql */
        /** @var array<int, mixed> $bindings */
        [$sql, $bindings] = $this->createSub($query);

        $bindings = array_merge($expressionBindings, $bindings);

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertUsing($this, $columns, $sql),
            $this->cleanBindings($bindings)
        );
    }

    /**
     * Update records in the database.
     *
     * @param array<string, mixed> $values
     * @return int
     */
    public function update(array $values)
    {
        $this->applyBeforeQueryCallbacks();

        /** @var \Staudenmeir\LaravelCte\Query\Grammars\ExpressionGrammar $grammar */
        $grammar = $this->grammar;

        $sql = $grammar->compileUpdate($this, $values);

        /** @var array{expressions: list<mixed>, select: list<mixed>, from: list<mixed>, join: list<mixed>,
         * where: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>,
         * unionOrder: list<mixed>} $bindings */
        $bindings = $this->bindings;

        return $this->connection->update($sql, $this->cleanBindings(
            $grammar->getBindingsForUpdate($this, $bindings, $values)
        ));
    }

    /**
     * Update records in a PostgreSQL database using the update from syntax.
     *
     * @param array<string, mixed> $values
     * @return int
     */
    public function updateFrom(array $values)
    {
        $this->applyBeforeQueryCallbacks();

        /** @var \Illuminate\Database\Query\Grammars\PostgresGrammar $grammar */
        $grammar = $this->grammar;

        $sql = $grammar->compileUpdateFrom($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $grammar->prepareBindingsForUpdateFrom($this->bindings, $values)
        ));
    }
}
