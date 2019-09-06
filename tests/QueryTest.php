<?php

namespace Tests;

use DateTime;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;
use Staudenmeir\LaravelCte\Query\Builder;

class QueryTest extends TestCase
{
    public function testWithExpression()
    {
        $rows = DB::table('u')
            ->select('u.id')
            ->withExpression('u', DB::table('users'))
            ->withExpression('p', function (Builder $query) {
                $query->from('posts');
            })
            ->join('p', 'p.user_id', '=', 'u.id')
            ->get();

        $this->assertEquals([1, 2], $rows->pluck('id')->all());
    }

    public function testWithExpressionMySql()
    {
        $builder = $this->getBuilder('MySql');
        $builder->select('u.id')
            ->from('u')
            ->withExpression('u', $this->getBuilder('MySql')->from('users'))
            ->withExpression('p', $this->getBuilder('MySql')->from('posts'))
            ->join('p', 'p.user_id', '=', 'u.id');

        $expected = 'with `u` as (select * from `users`), `p` as (select * from `posts`) select `u`.`id` from `u` inner join `p` on `p`.`user_id` = `u`.`id`';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithExpressionPostgres()
    {
        $builder = $this->getBuilder('Postgres');
        $builder->select('u.id')
            ->from('u')
            ->withExpression('u', $this->getBuilder('Postgres')->from('users'))
            ->withExpression('p', $this->getBuilder('Postgres')->from('posts'))
            ->join('p', 'p.user_id', '=', 'u.id');

        $expected = 'with "u" as (select * from "users"), "p" as (select * from "posts") select "u"."id" from "u" inner join "p" on "p"."user_id" = "u"."id"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithExpressionSQLite()
    {
        $builder = $this->getBuilder('SQLite');
        $builder->select('u.id')
            ->from('u')
            ->withExpression('u', $this->getBuilder('SQLite')->from('users'))
            ->withExpression('p', $this->getBuilder('SQLite')->from('posts'))
            ->join('p', 'p.user_id', '=', 'u.id');

        $expected = 'with "u" as (select * from "users"), "p" as (select * from "posts") select "u"."id" from "u" inner join "p" on "p"."user_id" = "u"."id"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithExpressionSqlServer()
    {
        $builder = $this->getBuilder('SqlServer');
        $builder->select('u.id')
            ->from('u')
            ->withExpression('u', $this->getBuilder('SqlServer')->from('users'))
            ->withExpression('p', $this->getBuilder('SqlServer')->from('posts'))
            ->join('p', 'p.user_id', '=', 'u.id');

        $expected = 'with [u] as (select * from [users]), [p] as (select * from [posts]) select [u].[id] from [u] inner join [p] on [p].[user_id] = [u].[id]';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithRecursiveExpression()
    {
        $query = 'select 1 union all select number + 1 from numbers where number < 3';

        $rows = DB::table('numbers')
            ->withRecursiveExpression('numbers', $query, ['number'])
            ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('number')->all());
    }

    public function testWithRecursiveExpressionMySql()
    {
        $query = $this->getBuilder('MySql')
            ->selectRaw('1')
            ->unionAll(
                $this->getBuilder('MySql')
                    ->selectRaw('number + 1')
                    ->from('numbers')
                    ->where('number', '<', 3)
            );
        $builder = $this->getBuilder('MySql');
        $builder->from('numbers')
            ->withRecursiveExpression('numbers', $query, ['number']);

        $expected = 'with recursive `numbers` (`number`) as ('.$query->toSql().') select * from `numbers`';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals([3], $builder->getRawBindings()['expressions']);
    }

    public function testWithRecursiveExpressionPostgres()
    {
        $query = $this->getBuilder('Postgres')
            ->selectRaw('1')
            ->unionAll(
                $this->getBuilder('Postgres')
                    ->selectRaw('number + 1')
                    ->from('numbers')
                    ->where('number', '<', 3)
            );
        $builder = $this->getBuilder('Postgres');
        $builder->from('numbers')
            ->withRecursiveExpression('numbers', $query, ['number']);

        $expected = 'with recursive "numbers" ("number") as ('.$query->toSql().') select * from "numbers"';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals([3], $builder->getRawBindings()['expressions']);
    }

    public function testWithRecursiveExpressionSQLite()
    {
        $query = $this->getBuilder('SQLite')
            ->selectRaw('1')
            ->unionAll(
                $this->getBuilder('SQLite')
                    ->selectRaw('number + 1')
                    ->from('numbers')
                    ->where('number', '<', 3)
            );
        $builder = $this->getBuilder('SQLite');
        $builder->from('numbers')
            ->withRecursiveExpression('numbers', $query, ['number']);

        $expected = 'with recursive "numbers" ("number") as (select * from (select 1) union all select number + 1 from "numbers" where "number" < ?) select * from "numbers"';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals([3], $builder->getRawBindings()['expressions']);
    }

    public function testUnionSQLite()
    {
        $builder = $this->getBuilder('SQLite')
            ->from('users')
            ->unionAll(
                $this->getBuilder('SQLite')
                    ->from('posts')
            );

        $expected = 'select * from (select * from "users") union all select * from (select * from "posts")';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithRecursiveExpressionSqlServer()
    {
        $query = $this->getBuilder('SqlServer')
            ->selectRaw('1')
            ->unionAll(
                $this->getBuilder('SqlServer')
                    ->selectRaw('number + 1')
                    ->from('numbers')
                    ->where('number', '<', 3)
            );
        $builder = $this->getBuilder('SqlServer');
        $builder->from('numbers')
            ->withRecursiveExpression('numbers', $query, ['number']);

        $expected = 'with [numbers] ([number]) as ('.$query->toSql().') select * from [numbers]';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals([3], $builder->getRawBindings()['expressions']);
    }

    public function testRecursionLimit()
    {
        $builder = $this->getBuilder('SqlServer');
        $builder->from('users')->recursionLimit(100);

        $this->assertEquals('select * from [users] option (maxrecursion 100)', $builder->toSql());
    }

    public function testInsertUsing()
    {
        DB::table('posts')
            ->withExpression('u', DB::table('users')->select('id')->where('id', '>', 1))
            ->insertUsing(['user_id'], DB::table('u'));

        $this->assertEquals([1, 2, 2, 3], DB::table('posts')->pluck('user_id')->all());
    }

    public function testInsertUsingWithRecursionLimit()
    {
        $builder = $this->getBuilder('SqlServer');
        $query = ' insert into [posts] ([id]) select [id] from [users] option (maxrecursion 100)';
        $builder->getConnection()->expects($this->once())->method('insert')->with($query, []);

        $builder->from('posts')
            ->recursionLimit(100)
            ->insertUsing(['id'], $this->getBuilder('SqlServer')->from('users')->select('id'));
    }

    public function testUpdate()
    {
        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->update([
                'user_id' => DB::raw('(select min(id) from u)'),
                'updated_at' => new DateTime(),
            ]);

        $this->assertEquals([2, 2], DB::table('posts')->pluck('user_id')->all());
    }

    public function testDelete()
    {
        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->whereIn('user_id', DB::table('u')->select('id'))
            ->delete();

        $this->assertEquals([1], DB::table('posts')->pluck('user_id')->all());
    }

    public function testOffsetSqlServer()
    {
        $expected = 'with [p] as (select * from [posts]) select * from (select *, row_number() over (order by (select 0)) as row_num from [users] inner join [p] on [p].[user_id] = [users].[id]) as temp_table where row_num >= 6 order by row_num';

        $query = $this->getBuilder('SqlServer')
            ->from('users')
            ->withExpression('p', $this->getBuilder('SqlServer')->from('posts'))
            ->join('p', 'p.user_id', '=', 'users.id')
            ->offset(5);

        $this->assertEquals($expected, $query->toSql());
    }

    protected function getBuilder($database)
    {
        $connection = $this->createMock(Connection::class);
        $grammar = 'Staudenmeir\LaravelCte\Query\Grammars\\'.$database.'Grammar';
        $processor = $this->createMock(Processor::class);

        return new Builder($connection, new $grammar, $processor);
    }

    protected function getPackageProviders($app)
    {
        return [DatabaseServiceProvider::class];
    }
}
