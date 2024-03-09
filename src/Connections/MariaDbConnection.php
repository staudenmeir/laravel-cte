<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\MariaDbConnection as Base;

class MariaDbConnection extends Base
{
    use CreatesQueryBuilder;
}
