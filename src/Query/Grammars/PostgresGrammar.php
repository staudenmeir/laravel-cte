<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesPostgresExpressions;

class PostgresGrammar extends Base
{
    use CompilesPostgresExpressions;
}
