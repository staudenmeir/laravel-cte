<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\SQLiteConnection as Base;

class SQLiteConnection extends Base
{
    use CreatesQueryBuilder;
}
