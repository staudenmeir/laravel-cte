<?php

namespace Staudenmeir\LaravelCte\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\LaravelCte\Tests\Models\Post;
use Staudenmeir\LaravelCte\Tests\Models\User;

abstract class TestCase extends Base
{
    protected string $connection;

    protected function setUp(): void
    {
        $this->connection = getenv('DB_CONNECTION') ?: 'sqlite';

        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('followers');
            $table->timestamps();

            if ($this->connection === 'singlestore') {
                /** @var \SingleStore\Laravel\Schema\Blueprint $table */
                $table->shardKey('id');
            }
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('views');
            $table->timestamps();

            if ($this->connection === 'singlestore') {
                /** @var \SingleStore\Laravel\Schema\Blueprint $table */
                $table->shardKey('id');
            }
        });

        Model::unguard();

        User::create(['id' => 1, 'parent_id' => null, 'followers' => 10]);
        User::create(['id' => 2, 'parent_id' => 1, 'followers' => 20]);
        User::create(['id' => 3, 'parent_id' => 2, 'followers' => 30]);

        Post::create(['id' => 11, 'user_id' => 1, 'views' => 0]);
        Post::create(['id' => 12, 'user_id' => 2, 'views' => 0]);

        Model::reguard();
    }

    protected function tearDown(): void
    {
        DB::connection()->disconnect();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[$this->connection]);
    }

    protected function getPackageProviders($app)
    {
        return []; // TODO[L12]
//        return [Oci8ServiceProvider::class, SingleStoreProvider::class, FirebirdServiceProvider::class];
    }
}
