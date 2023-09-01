<?php

namespace Staudenmeir\LaravelCte\Tests;

use DateTime;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;
use Staudenmeir\LaravelCte\Query\Builder;
use Staudenmeir\LaravelCte\Query\SingleStoreBuilder;

class QueryTest extends TestCase
{
    public function testWithExpression()
    {
        $posts = function (BaseBuilder $query) {
            $query->from('posts');
        };

        $rows = DB::table('u')
            ->select('u.id')
            ->withExpression('u', DB::table('users'))
            ->withExpression('p', $posts)
            ->join('p', 'p.user_id', '=', 'u.id')
            ->orderBy('u.id')
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

    public function testWithExpressionSingleStore()
    {
        $builder = $this->getBuilder('SingleStore');
        $builder->select('u.id')
                ->from('u')
                ->withExpression('u', $this->getBuilder('SingleStore')->from('users'))
                ->withExpression('p', $this->getBuilder('SingleStore')->from('posts'))
                ->join('p', 'p.user_id', '=', 'u.id');

        $expected = 'with `u` as (select * from `users`), `p` as (select * from `posts`) select `u`.`id` from `u` inner join `p` on `p`.`user_id` = `u`.`id`';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithRecursiveExpression()
    {
        // SingleStore doesn't support previous variant of the RCTE
        // It throws the following error:
        // Unsupported recursive common table expression query shape: recursive CTE select cannot be materialized.
        if ($this->database === 'singlestore') {
            $query = 'select 1 as number from `users` limit 1 union all select number + 1 from numbers where number < 3';
        } else {
            $query = 'select 1 union all select number + 1 from numbers where number < 3';
        }

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

    public function testWithRecursiveExpressionSingleStore()
    {
        $query = $this->getBuilder('SingleStore')
                      ->selectRaw('1')
                      ->unionAll(
                          $this->getBuilder('SingleStore')
                               ->selectRaw('number + 1')
                               ->from('numbers')
                               ->where('number', '<', 3)
                      );
        $builder = $this->getBuilder('SingleStore');
        $builder->from('numbers')
                ->withRecursiveExpression('numbers', $query, ['number']);

        $expected = 'with recursive `numbers` (`number`) as ('.$query->toSql().') select * from `numbers`';
        $this->assertEquals($expected, $builder->toSql());
        $this->assertEquals([3], $builder->getRawBindings()['expressions']);
    }

    public function testWithRecursiveExpressionAndCycleDetection()
    {
        if (!in_array($this->database, ['mariadb', 'pgsql'])) {
            $this->markTestSkipped();
        }

        $query = 'select 1, 1 union all select number + 1, (number + 1) % 5 from numbers';

        $rows = DB::table('numbers')
                  ->withRecursiveExpressionAndCycleDetection('numbers', $query, 'modulo', 'is_cycle', 'path', ['number', 'modulo'])
                  ->get();

        if ($this->database === 'mariadb') {
            $this->assertEquals([1, 2, 3, 4, 5], $rows->pluck('number')->all());
        }

        if ($this->database === 'pgsql') {
            $this->assertEquals([1, 2, 3, 4, 5, 6], $rows->pluck('number')->all());
            $this->assertSame(false, $rows[0]->is_cycle);
            $this->assertEquals('{(1)}', $rows[0]->path);
        }
    }

    public function testWithMaterializedExpression()
    {
        if (!in_array($this->database, ['pgsql', 'sqlite'])) {
            $this->markTestSkipped();
        }

        $rows = DB::table('u')
                  ->select('u.id')
                  ->withMaterializedExpression('u', DB::table('users'))
                  ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('id')->all());
    }

    public function testWithMaterializedExpressionPostgres()
    {
        $builder = $this->getBuilder('Postgres');
        $builder->select('u.id')
                ->from('u')
                ->withMaterializedExpression('u', $this->getBuilder('Postgres')->from('users'));

        $expected = 'with "u" as materialized (select * from "users") select "u"."id" from "u"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithMaterializedExpressionSQLite()
    {
        $builder = $this->getBuilder('SQLite');
        $builder->select('u.id')
                ->from('u')
                ->withMaterializedExpression('u', $this->getBuilder('SQLite')->from('users'));

        $expected = 'with "u" as materialized (select * from "users") select "u"."id" from "u"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithNonMaterializedExpression()
    {
        if (!in_array($this->database, ['pgsql', 'sqlite'])) {
            $this->markTestSkipped();
        }

        $rows = DB::table('u')
                  ->select('u.id')
                  ->withNonMaterializedExpression('u', DB::table('users'))
                  ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('id')->all());
    }

    public function testWithNonMaterializedExpressionPostgres()
    {
        $builder = $this->getBuilder('Postgres');
        $builder->select('u.id')
                ->from('u')
                ->withNonMaterializedExpression('u', $this->getBuilder('Postgres')->from('users'));

        $expected = 'with "u" as not materialized (select * from "users") select "u"."id" from "u"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testWithNonMaterializedExpressionSQLite()
    {
        $builder = $this->getBuilder('SQLite');
        $builder->select('u.id')
                ->from('u')
                ->withNonMaterializedExpression('u', $this->getBuilder('SQLite')->from('users'));

        $expected = 'with "u" as not materialized (select * from "users") select "u"."id" from "u"';
        $this->assertEquals($expected, $builder->toSql());
    }

    public function testRecursionLimit()
    {
        $builder = $this->getBuilder('SqlServer');
        $builder->from('users')->recursionLimit(100);

        $this->assertEquals('select * from [users] option (maxrecursion 100)', $builder->toSql());
    }

    public function testOuterUnion()
    {
        $rows = DB::table('u')
                   ->where('id', 1)
                   ->unionAll(
                       DB::table('u')
                         ->where('id', 2)
                   )
                   ->withExpression('u', DB::table('users'))
                   ->get();

        $this->assertEquals([1, 2], $rows->pluck('id')->all());
    }

    public function testOuterUnionWithRecursionLimit()
    {
        $builder = $this->getBuilder('SqlServer');
        $builder->from('users')->recursionLimit(100);

        $builder = $this->getBuilder('SqlServer')
                        ->from('u')
                        ->where('id', 1)
                        ->unionAll(
                            $this->getBuilder('SqlServer')
                                 ->from('u')
                                 ->where('id', 2)
                        )
                        ->withExpression('u', $this->getBuilder('SqlServer')->from('users'))
                        ->recursionLimit(100);

        $expected = <<<EOT
with [u] as (select * from [users]) select * from (select * from [u] where [id] = ?) as [temp_table] union all select * from (select * from [u] where [id] = ?) as [temp_table] option (maxrecursion 100)
EOT;

        $this->assertEquals($expected, $builder->toSql());
    }

    public function testInsertUsing()
    {
        DB::table('posts')
            ->withExpression('u', DB::table('users')->select('id')->where('id', '>', 1))
            ->insertUsing(['user_id'], DB::table('u'));

        $this->assertEquals([1, 2, 2, 3], DB::table('posts')->orderBy('user_id')->pluck('user_id')->all());
    }

    public function testInsertUsingWithRecursionLimit()
    {
        $builder = $this->getBuilder('SqlServer');
        $query = 'insert into [posts] ([id]) select [id] from [users] option (maxrecursion 100)';
        $builder->getConnection()->expects($this->once())->method('affectingStatement')->with($query, []);

        $builder->from('posts')
            ->recursionLimit(100)
            ->insertUsing(['id'], $this->getBuilder('SqlServer')->from('users')->select('id'));
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->update([
                'views' => DB::raw('(select count(*) from u)'),
                'updated_at' => new DateTime(),
            ]);

        $this->assertEquals([2, 2], DB::table('posts')->orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithJoin()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->join('u', 'u.id', '=', 'posts.user_id')
            ->update([
                'views' => 1
            ]);

        $this->assertEquals([0, 1], DB::table('posts')->orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithLimit()
    {
        // SingleStore support update with limit only when it is constrained to a single partition
        // https://docs.singlestore.com/cloud/reference/sql-reference/data-manipulation-language-dml/update/#update-using-limit
        if (in_array($this->database, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 0))
            ->whereIn('user_id', DB::table('u')->select('id'))
            ->orderBy('id')
            ->limit(1)
            ->update([
                'views' => 1,
            ]);

        $this->assertEquals([1, 0], DB::table('posts')->orderBy('id')->pluck('views')->all());
    }

    public function testUpdateFrom()
    {
        if ($this->database !== 'pgsql') {
            $this->markTestSkipped();
        }

        DB::table('posts')
          ->withExpression('u', DB::table('users')->where('id', '>', 1))
          ->join('u', 'u.id', '=', 'posts.user_id')
          ->updateFrom([
              'views' => DB::raw('"u"."followers"'),
          ]);

        $this->assertEquals([0, 20], DB::table('posts')->orderBy('id')->pluck('views')->all());
    }

    public function testDelete()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->whereIn('user_id', DB::table('u')->select('id'))
            ->delete();

        $this->assertEquals([1], DB::table('posts')->pluck('user_id')->all());
    }

    public function testDeleteWithJoin()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        DB::table('posts')
            ->withExpression('u', DB::table('users')->where('id', '>', 1))
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->whereIn('user_id', DB::table('u')->select('id'))
            ->delete();

        $this->assertEquals([1], DB::table('posts')->pluck('user_id')->all());
    }

    public function testDeleteWithLimit()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        if ($this->database === 'singlestore') {
            $query = DB::table('posts')
                ->withExpression('u', DB::table('users')->where('id', '<', 2));
        } else {
            $query = DB::table('posts')
                  ->withExpression('u', DB::table('users')->where('id', '>', 0))
                  ->orderBy('id');
        }

        $query->whereIn('user_id', DB::table('u')->select('id'))
              ->limit(1)
              ->delete();

        $this->assertEquals([2], DB::table('posts')->pluck('user_id')->all());
    }

    public function testOffsetSqlServer()
    {
        $expected = 'with [p] as (select * from [posts]) select * from [users] inner join [p] on [p].[user_id] = [users].[id] order by (SELECT 0) offset 5 rows';

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

        return match ($database) {
            'SingleStore' => new SingleStoreBuilder($connection, new $grammar(), $processor),
            default => new Builder($connection, new $grammar(), $processor),
        };
    }

    protected function getPackageProviders($app)
    {
        return array_merge(
            parent::getPackageProviders($app),
            [DatabaseServiceProvider::class]
        );
    }
}
