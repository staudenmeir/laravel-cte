<?php

namespace Staudenmeir\LaravelCte;

use Illuminate\Support\ServiceProvider;
use Staudenmeir\LaravelCte\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });
    }
}
