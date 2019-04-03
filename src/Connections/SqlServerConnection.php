<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\SqlServerConnection as Base;

class SqlServerConnection extends Base
{
    use CreatesQueryBuilder;
}
