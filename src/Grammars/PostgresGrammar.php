<?php

namespace Staudenmeir\LaravelCte\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;

class PostgresGrammar extends Base
{
    use CompilesExpressions;
}
