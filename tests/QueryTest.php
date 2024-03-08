<?php

namespace Staudenmeir\LaravelCte\Tests;

use DateTime;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;

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

    public function testWithRecursiveExpression()
    {
        $query = match ($this->connection) {
            'singlestore' => 'select 1 as number from `users` limit 1 union all select number + 1 from numbers where number < 3',
            'firebird' => 'select 1 from RDB$DATABASE union all select "number" + 1 from "numbers" where "number" < 3',
            default => 'select 1 union all select number + 1 from numbers where number < 3',
        };

        $rows = DB::table('numbers')
            ->withRecursiveExpression('numbers', $query, ['number'])
            ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('number')->all());
    }

    public function testWithRecursiveExpressionAndCycleDetection()
    {
        if (!in_array($this->connection, ['mariadb', 'pgsql'])) {
            $this->markTestSkipped();
        }

        $query = 'select 1, 1 union all select number + 1, (number + 1) % 5 from numbers';

        $rows = DB::table('numbers')
                  ->withRecursiveExpressionAndCycleDetection('numbers', $query, 'modulo', 'is_cycle', 'path', ['number', 'modulo'])
                  ->get();

        if ($this->connection === 'mariadb') {
            $this->assertEquals([1, 2, 3, 4, 5], $rows->pluck('number')->all());
        }

        if ($this->connection === 'pgsql') {
            $this->assertEquals([1, 2, 3, 4, 5, 6], $rows->pluck('number')->all());
            $this->assertSame(false, $rows[0]->is_cycle);
            $this->assertEquals('{(1)}', $rows[0]->path);
        }
    }

    public function testWithMaterializedExpression()
    {
        if (!in_array($this->connection, ['pgsql', 'sqlite'])) {
            $this->markTestSkipped();
        }

        $rows = DB::table('u')
                  ->select('u.id')
                  ->withMaterializedExpression('u', DB::table('users'))
                  ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('id')->all());
    }

    public function testWithNonMaterializedExpression()
    {
        if (!in_array($this->connection, ['pgsql', 'sqlite'])) {
            $this->markTestSkipped();
        }

        $rows = DB::table('u')
                  ->select('u.id')
                  ->withNonMaterializedExpression('u', DB::table('users'))
                  ->get();

        $this->assertEquals([1, 2, 3], $rows->pluck('id')->all());
    }

    public function testRecursionLimit()
    {
        if ($this->connection !== 'sqlsrv') {
            $this->markTestSkipped();
        }

        $query = 'select 1 union all select number + 1 from numbers where number < 102';

        $rows = DB::table('numbers')
            ->withRecursiveExpression('numbers', $query, ['number'])
            ->recursionLimit(101)
            ->get();

        $this->assertCount(102, $rows);
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
                   ->when($this->connection !== 'firebird', fn (BaseBuilder $query) => $query->orderBy('id'))
                   ->get();

        $this->assertEquals([1, 2], $rows->pluck('id')->all());
    }

    public function testInsertUsing()
    {
        $id = match ($this->connection) {
            'firebird' => '(select max("id") from "posts") + "id" as "id"',
            default => '(select max(id) from posts) + id as id',
        };

        $query = DB::table('users')
            ->selectRaw($id)
            ->addSelect('id as post_id')
            ->selectRaw('1 as views')
            ->where('id', '>', 1);

        DB::table('posts')
            ->withExpression('u', $query)
            ->insertUsing(['id', 'user_id', 'views'], DB::table('u'));

        $this->assertEquals([1, 2, 2, 3], DB::table('posts')->orderBy('user_id')->pluck('user_id')->all());
    }

    public function testInsertUsingWithRecursionLimit()
    {
        if ($this->connection !== 'sqlsrv') {
            $this->markTestSkipped();
        }

        $numbers = 'select 1 union all select number + 1 from numbers where number < 102';

        $users = DB::table('numbers')
            ->selectRaw('(select max(id) from users) + number as id')
            ->selectRaw('0 as followers');

        DB::table('users')
            ->withRecursiveExpression('numbers', $numbers, ['number'])
            ->withExpression('u', $users)
            ->recursionLimit(101)
            ->insertUsing(['id', 'followers'], DB::table('u'));

        $this->assertEquals(105, DB::table('users')->count());
    }

    public function testUpdate()
    {
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
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
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
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
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
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
        if ($this->connection !== 'pgsql') {
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
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
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
        if (in_array($this->connection, ['mariadb', 'firebird'])) {
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
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'firebird'])) {
            $this->markTestSkipped();
        }

        if ($this->connection === 'singlestore') {
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

    public function testOffset()
    {
        if ($this->connection !== 'sqlsrv') {
            $this->markTestSkipped();
        }

        $rows = DB::table('u')
            ->withExpression('u', DB::table('users'))
            ->limit(1)
            ->offset(1)
            ->get();

        $this->assertEquals([2], $rows->pluck('id')->all());
    }

    protected function getPackageProviders($app)
    {
        return array_merge(
            parent::getPackageProviders($app),
            [DatabaseServiceProvider::class]
        );
    }
}
