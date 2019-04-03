<?php

namespace Staudenmeir\LaravelCte\Connections;

use Illuminate\Database\MySqlConnection as Base;

class MySqlConnection extends Base
{
    use CreatesQueryBuilder;
}
