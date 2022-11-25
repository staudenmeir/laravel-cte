<?php

namespace Staudenmeir\LaravelCte\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\LaravelCte\Tests\Models\Post;
use Staudenmeir\LaravelCte\Tests\Models\User;

abstract class TestCase extends Base
{
    protected string $database;

    protected function setUp(): void
    {
        $this->database = getenv('DATABASE') ?: 'sqlite';

        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedBigInteger('followers');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        Model::unguard();

        User::create(['parent_id' => null, 'followers' => 10]);
        User::create(['parent_id' => 1, 'followers' => 20]);
        User::create(['parent_id' => 2, 'followers' => 30]);

        Post::create(['user_id' => 1]);
        Post::create(['user_id' => 2]);

        Model::reguard();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[$this->database]);
    }
}
