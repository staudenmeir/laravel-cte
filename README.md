[![Build Status](https://travis-ci.org/staudenmeir/laravel-cte.svg?branch=master)](https://travis-ci.org/staudenmeir/laravel-cte)
[![Code Coverage](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-cte/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-cte/v/stable)](https://packagist.org/packages/staudenmeir/laravel-cte)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-cte/downloads)](https://packagist.org/packages/staudenmeir/laravel-cte)
[![License](https://poser.pugx.org/staudenmeir/laravel-cte/license)](https://packagist.org/packages/staudenmeir/laravel-cte)

## Introduction
This Laravel extension adds support for common table expressions (CTE) to the query builder and Eloquent.

Supports Laravel 5.5+.

## Compatibility

- MySQL 8.0+
- MariaDB 10.2+
- PostgreSQL 9.4+
- SQLite 3.8.3+
- SQL Server 2008+
 
## Installation

    composer require staudenmeir/laravel-cte:"^1.0"

## Usage

- [SELECT Queries](#select-queries)
- [INSERT/UPDATE/DELETE Queries](#insertupdatedelete-queries)
- [Eloquent](#eloquent)
  - [Recursive Relationships](#recursive-relationships)
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

You can provide the expression's columns as the third argument:

```php
$query = 'select 1 union all select number + 1 from numbers where number < 10';

$numbers = DB::table('numbers')
    ->withRecursiveExpression('numbers', $query, ['number'])
    ->get();
```

### INSERT/UPDATE/DELETE Queries

You can use common table expressions in `INSERT`(Laravel 5.7.17+), `UPDATE` and `DELETE` queries:

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

You can use common table expressions in Eloquent queries with the `QueriesExpressions` trait:

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

If you want to implement recursive relationships, you can use this package: [staudenmeir/laravel-adjacency-list](https://github.com/staudenmeir/laravel-adjacency-list)

### Lumen

If you are using Lumen, you have to instantiate the query builder manually:

```php
$builder = new \Staudenmeir\LaravelCte\Query\Builder(app('db')->connection());

$result = $builder->from(...)->withExpression(...)->get();
```