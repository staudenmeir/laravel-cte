<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\PostgresConnection as Base;

class PostgresConnection extends Base
{
    use CreatesQueryBuilder;
}
