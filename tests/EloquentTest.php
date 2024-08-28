<?php

namespace Staudenmeir\LaravelCte\Tests;

use DateTime;
use Illuminate\Database\Query\Expression;
use Staudenmeir\LaravelCte\Tests\Models\Post;
use Staudenmeir\LaravelCte\Tests\Models\User;

class EloquentTest extends TestCase
{
    public function testWithExpression()
    {
        $users = User::query()->withExpression('u', User::where('id', '>', 1))
            ->from('u')
            ->orderBy('id')
            ->get();

        $this->assertEquals([2, 3], $users->pluck('id')->all());
    }

    public function testWithRecursiveExpression()
    {
        $query = User::select('id', 'parent_id', 'followers', 'created_at', 'updated_at')
            ->where('id', 3)
            ->unionAll(
                User::select('users.id', 'users.parent_id', 'users.followers', 'users.created_at', 'users.updated_at')
                    ->join('ancestors', 'ancestors.parent_id', '=', 'users.id')
            );

        $users = User::from('ancestors')
            ->withRecursiveExpression('ancestors', $query, ['id', 'parent_id', 'followers', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get();

        $this->assertEquals([1, 2, 3], $users->pluck('id')->all());
    }

    public function testWithRecursiveExpressionAndCycleDetection()
    {
        if (!in_array($this->connection, ['mariadb', 'pgsql'])) {
            $this->markTestSkipped();
        }

        User::where('id', 1)->update(['parent_id' => 3]);

        $query = User::where('id', 3)
                     ->unionAll(
                         User::select('users.*')
                             ->join('ancestors', 'ancestors.parent_id', '=', 'users.id')
                     );

        $users = User::from('ancestors')
                     ->withRecursiveExpressionAndCycleDetection('ancestors', $query, 'id', 'is_cycle', 'path')
                     ->get();

        if ($this->connection === 'mariadb') {
            $this->assertEquals([3, 2, 1], $users->pluck('id')->all());
        }

        if ($this->connection === 'pgsql') {
            $this->assertEquals([3, 2, 1, 3], $users->pluck('id')->all());
            $this->assertSame(false, $users[0]->is_cycle);
            $this->assertEquals('{(3)}', $users[0]->path);
        }
    }

    public function testOuterUnion()
    {
        $users = User::where('id', 1)
                  ->unionAll(
                      User::where('id', 2)
                  )
                  ->withExpression('u', User::query())
                  ->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testInsertUsing()
    {
        $id = match ($this->connection) {
            'firebird' => '(select max("id") from "posts") + "id" as "id"',
            default => '(select max(id) from posts) + id as id',
        };

        $query = User::selectRaw($id)
            ->addSelect('id as post_id')
            ->selectRaw('1 as views')
            ->where('id', '>', 1);

        Post::query()->withExpression('u', $query)
          ->insertUsing(['id', 'user_id', 'views'], User::from('u'));

        $this->assertEquals([1, 2, 2, 3], Post::orderBy('user_id')->pluck('user_id')->all());
    }

    public function testUpdate()
    {
        if (in_array($this->connection, ['mariadb', 'oracle', 'firebird'])) {
            $this->markTestSkipped();
        }

        Post::query()->withExpression('u', User::where('id', '>', 1))
          ->update([
              'views' => new Expression('(select count(*) from u)'),
              'updated_at' => new DateTime(),
          ]);

        $this->assertEquals([2, 2], Post::orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithJoin()
    {
        if (in_array($this->connection, ['mariadb', 'oracle', 'firebird'])) {
            $this->markTestSkipped();
        }

        Post::query()->withExpression('u', User::where('id', '>', 1))
          ->join('u', 'u.id', '=', 'posts.user_id')
          ->update([
              'views' => 1
          ]);

        $this->assertEquals([0, 1], Post::orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithLimit()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'oracle', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        Post::query()->withExpression('u', User::where('id', '>', 0))
          ->whereIn('user_id', User::from('u')->select('id'))
          ->orderBy('id')
          ->limit(1)
          ->update([
              'views' => 1,
          ]);

        $this->assertEquals([1, 0], Post::orderBy('id')->pluck('views')->all());
    }

    public function testDelete()
    {
        if (in_array($this->connection, ['mariadb', 'oracle', 'firebird'])) {
            $this->markTestSkipped();
        }

        Post::query()->withExpression('u', User::where('id', '>', 1))
          ->whereIn('user_id', User::from('u')->select('id'))
          ->delete();

        $this->assertEquals([1], Post::pluck('user_id')->all());
    }

    public function testDeleteWithJoin()
    {
        if (in_array($this->connection, ['mariadb', 'oracle', 'firebird'])) {
            $this->markTestSkipped();
        }

        Post::query()->withExpression('u', User::where('id', '>', 1))
          ->join('users', 'users.id', '=', 'posts.user_id')
          ->whereIn('user_id', User::from('u')->select('id'))
          ->delete();

        $this->assertEquals([1], Post::pluck('user_id')->all());
    }

    public function testDeleteWithLimit()
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'oracle', 'firebird'])) {
            $this->markTestSkipped();
        }

        if ($this->connection === 'singlestore') {
            $query = Post::query()->withExpression('u', User::where('id', '<', 2));
        } else {
            $query = Post::query()->withExpression('u', User::where('id', '>', 0))
                         ->orderBy('id');
        }

        $query->whereIn('user_id', User::from('u')->select('id'))
              ->limit(1)
              ->delete();

        $this->assertEquals([2], Post::pluck('user_id')->all());
    }
}
