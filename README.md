# Laravel CTE

[![CI](https://github.com/staudenmeir/laravel-cte/actions/workflows/ci.yml/badge.svg)](https://github.com/staudenmeir/laravel-cte/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/staudenmeir/laravel-cte/graph/badge.svg?token=JWHOOEYYGG)](https://codecov.io/gh/staudenmeir/laravel-cte)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-cte/v/stable)](https://packagist.org/packages/staudenmeir/laravel-cte)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-cte/downloads)](https://packagist.org/packages/staudenmeir/laravel-cte/stats)
[![License](https://poser.pugx.org/staudenmeir/laravel-cte/license)](https://github.com/staudenmeir/laravel-cte/blob/master/LICENSE)

This Laravel extension adds support for common table expressions (CTE) to the query builder and Eloquent.

Supports Laravel 5.5+.

## Compatibility

- MySQL 8.0+
- MariaDB 10.2+
- PostgreSQL 9.4+
- SQLite 3.8.3+
- SQL Server 2008+
- Oracle 9.2+
- SingleStore 8.1+
- Firebird

## Installation

    composer require staudenmeir/laravel-cte:"^1.0"

Use this command if you are in PowerShell on Windows (e.g. in VS Code):

    composer require staudenmeir/laravel-cte:"^^^^1.0"

## Versions

| Laravel | Package |
|:--------|:--------|
| 11.x    | 1.11    |
| 10.x    | 1.9     |
| 9.x     | 1.6     |
| 8.x     | 1.5     |
| 7.x     | 1.4     |
| 6.x     | 1.2     |
| 5.8     | 1.1     |
| 5.5–5.7 | 1.0     |

## Usage

- [SELECT Queries](#select-queries)
    - [Recursive Expressions](#recursive-expressions)
    - [Materialized Expressions](#materialized-expressions)
    - [Custom Columns](#custom-columns)
    - [Cycle Detection](#cycle-detection)
- [INSERT/UPDATE/DELETE Queries](#insertupdatedelete-queries)
- [Eloquent](#eloquent)
    - [Recursive Relationships](#recursive-relationships)
- [Package Conflicts](#package-conflicts)
- [Lumen](#lumen)

### SELECT Queries

Use `withExpression()` and provide a query builder instance, an SQL string or a closure:

```php
$posts = DB::table('p')
    ->select('p.*', 'u.name')
    ->withExpression('p', DB::table('posts'))
    ->withExpression('u', function ($query) {
        $query->from('users');
    })
    ->join('u', 'u.id', '=', 'p.user_id')
    ->get();
```

#### Recursive Expressions

Use `withRecursiveExpression()` for recursive expressions:

```php
$query = DB::table('users')
    ->whereNull('parent_id')
    ->unionAll(
        DB::table('users')
            ->select('users.*')
            ->join('tree', 'tree.id', '=', 'users.parent_id')
    );

$tree = DB::table('tree')
    ->withRecursiveExpression('tree', $query)
    ->get();
```

#### Materialized Expressions

Use `withMaterializedExpression()`/`withNonMaterializedExpression()` for (non-)materialized expressions (PostgreSQL,
SQLite):

```php
$posts = DB::table('p')
    ->select('p.*', 'u.name')
    ->withMaterializedExpression('p', DB::table('posts'))
    ->withNonMaterializedExpression('u', function ($query) {
        $query->from('users');
    })
    ->join('u', 'u.id', '=', 'p.user_id')
    ->get();
```

#### Custom Columns

You can provide the expression's columns as the third argument:

```php
$query = 'select 1 union all select number + 1 from numbers where number < 10';

$numbers = DB::table('numbers')
    ->withRecursiveExpression('numbers', $query, ['number'])
    ->get();
```

#### Cycle Detection

[MariaDB 10.5.2+](https://mariadb.com/kb/en/with/#cycle-restrict)
and [PostgreSQL 14+](https://www.postgresql.org/docs/current/queries-with.html#QUERIES-WITH-CYCLE) support native cycle
detection to prevent infinite loops in recursive expressions. Provide the column(s) that indicate(s) a cycle as the
third argument to `withRecursiveExpressionAndCycleDetection()`:

```php
$query = DB::table('users')
    ->whereNull('parent_id')
    ->unionAll(
        DB::table('users')
            ->select('users.*')
            ->join('tree', 'tree.id', '=', 'users.parent_id')
    );

$tree = DB::table('tree')
    ->withRecursiveExpressionAndCycleDetection('tree', $query, 'id')
    ->get();
```

On PostgreSQL, you can customize the name of the column that shows whether a cycle has been detected and the name of the
column that tracks the path:

```php
$tree = DB::table('tree')
    ->withRecursiveExpressionAndCycleDetection('tree', $query, 'id', 'is_cycle', 'path')
    ->get();
```

### INSERT/UPDATE/DELETE Queries

You can use common table expressions in `INSERT`, `UPDATE` and `DELETE` queries:

```php
DB::table('profiles')
    ->withExpression('u', DB::table('users')->select('id', 'name'))
    ->insertUsing(['user_id', 'name'], DB::table('u'));
```

```php
DB::table('profiles')
    ->withExpression('u', DB::table('users'))
    ->join('u', 'u.id', '=', 'profiles.user_id')
    ->update(['profiles.name' => DB::raw('u.name')]);
```

```php
DB::table('profiles')
    ->withExpression('u', DB::table('users')->where('active', false))
    ->whereIn('user_id', DB::table('u')->select('id'))
    ->delete();
```

### Eloquent

You can use common table expressions in Eloquent queries.

In Laravel 5.5–5.7, this requires the `QueriesExpressions` trait:

```php
class User extends Model
{
    use \Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;
}

$query = User::whereNull('parent_id')
    ->unionAll(
        User::select('users.*')
            ->join('tree', 'tree.id', '=', 'users.parent_id')
    );

$tree = User::from('tree')
    ->withRecursiveExpression('tree', $query)
    ->get();
```

#### Recursive Relationships

If you want to implement recursive relationships, you can use this
package: [staudenmeir/laravel-adjacency-list](https://github.com/staudenmeir/laravel-adjacency-list)

### Package Conflicts

- `staudenmeir/eloquent-eager-limit`: Replace both packages
  with [staudenmeir/eloquent-eager-limit-x-laravel-cte](https://github.com/staudenmeir/eloquent-eager-limit-x-laravel-cte)
  to use them on the same model.

### Lumen

If you are using Lumen, you have to instantiate the query builder manually:

```php
$builder = new \Staudenmeir\LaravelCte\Query\Builder(app('db')->connection());

$result = $builder->from(...)->withExpression(...)->get();
```

In Eloquent, the `QueriesExpressions` trait is required for *all* versions of Lumen.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.
