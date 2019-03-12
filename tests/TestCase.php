<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;

abstract class TestCase extends Base
{
    protected function setUp()
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        DB::table('users')->insert([
            ['parent_id' => null],
            ['parent_id' => 1],
            ['parent_id' => 2],
        ]);
        DB::table('posts')->insert([
            ['user_id' => 1],
            ['user_id' => 2],
        ]);
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[getenv('DB') ?: 'sqlite']);
    }

    protected function getPackageProviders($app)
    {
        return [DatabaseServiceProvider::class];
    }
}
