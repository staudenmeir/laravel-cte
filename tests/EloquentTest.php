<?php

namespace Tests;

use Staudenmeir\LaravelCte\Query\Builder;
use Tests\Models\User;

class EloquentTest extends TestCase
{
    public function testWithExpression()
    {
        $users = User::withExpression('ids', 'select 1 union all select 2', ['id'])
            ->whereIn('id', function (Builder $query) {
                $query->from('ids');
            })->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testWithRecursiveExpression()
    {
        $query = User::query()
            ->where('id', 3)
            ->unionAll(
                User::query()
                    ->select('users.*')
                    ->join('parents', 'parents.parent_id', '=', 'users.id')
            );

        $users = User::from('parents')
            ->withRecursiveExpression('parents', $query)
            ->get();

        $this->assertEquals([3, 2, 1], $users->pluck('id')->all());
    }
}
