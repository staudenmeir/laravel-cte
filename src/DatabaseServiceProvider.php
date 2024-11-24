<?php

namespace Staudenmeir\LaravelCte;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelCte\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends ServiceProvider
{
    /** @inheritDoc */
    public function register()
    {
        $this->app->singleton('db.factory', function (Container $app) {
            return new ConnectionFactory($app);
        });
    }
}
