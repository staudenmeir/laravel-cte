<?php

namespace Staudenmeir\LaravelCte\Tests;

use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Staudenmeir\LaravelCte\Tests\Models\Post;
use Staudenmeir\LaravelCte\Tests\Models\User;

class EloquentTest extends TestCase
{
    public function testWithExpression()
    {
        $users = User::withExpression('ids', 'select 1 union all select 2', ['id'])
            ->whereIn('id', function (Builder $query) {
                $query->from('ids');
            })->orderBy('id')->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testWithRecursiveExpression()
    {
        $query = User::where('id', 3)
            ->unionAll(
                User::select('users.*')
                    ->join('ancestors', 'ancestors.parent_id', '=', 'users.id')
            );

        $users = User::from('ancestors')
            ->withRecursiveExpression('ancestors', $query)
            ->orderBy('id')
            ->get();

        $this->assertEquals([1, 2, 3], $users->pluck('id')->all());
    }

    public function testWithRecursiveExpressionAndCycleDetection()
    {
        if (!in_array($this->database, ['mariadb', 'pgsql'])) {
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

        if ($this->database === 'mariadb') {
            $this->assertEquals([3, 2, 1], $users->pluck('id')->all());
        }

        if ($this->database === 'pgsql') {
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
        Post::withExpression('u', User::select('id')->where('id', '>', 1))
          ->insertUsing(['user_id'], User::from('u'));

        $this->assertEquals([1, 2, 2, 3], Post::orderBy('user_id')->pluck('user_id')->all());
    }

    public function testUpdate()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        Post::withExpression('u', User::where('id', '>', 1))
          ->update([
              'views' => new Expression('(select count(*) from u)'),
              'updated_at' => new DateTime(),
          ]);

        $this->assertEquals([2, 2], Post::orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithJoin()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        Post::withExpression('u', User::where('id', '>', 1))
          ->join('u', 'u.id', '=', 'posts.user_id')
          ->update([
              'views' => 1
          ]);

        $this->assertEquals([0, 1], Post::orderBy('id')->pluck('views')->all());
    }

    public function testUpdateWithLimit()
    {
        // SingleStore support update with limit only when it is constrained to a single partition
        // https://docs.singlestore.com/cloud/reference/sql-reference/data-manipulation-language-dml/update/#update-using-limit
        if (in_array($this->database, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        Post::withExpression('u', User::where('id', '>', 0))
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
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        Post::withExpression('u', User::where('id', '>', 1))
          ->whereIn('user_id', User::from('u')->select('id'))
          ->delete();

        $this->assertEquals([1], Post::pluck('user_id')->all());
    }

    public function testDeleteWithJoin()
    {
        if ($this->database === 'mariadb') {
            $this->markTestSkipped();
        }

        Post::withExpression('u', User::where('id', '>', 1))
          ->join('users', 'users.id', '=', 'posts.user_id')
          ->whereIn('user_id', User::from('u')->select('id'))
          ->delete();

        $this->assertEquals([1], Post::pluck('user_id')->all());
    }

    public function testDeleteWithLimit()
    {
        if (in_array($this->database, ['mariadb', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        if ($this->database === 'singlestore') {
            $query = Post::withExpression('u', User::where('id', '<', 2));
        } else {
            $query = Post::withExpression('u', User::where('id', '>', 0))
                         ->orderBy('id');
        }

        $query->whereIn('user_id', User::from('u')->select('id'))
              ->limit(1)
              ->delete();

        $this->assertEquals([2], Post::pluck('user_id')->all());
    }
}
