<?php

namespace Staudenmeir\LaravelCte\Grammars;

use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;

class SQLiteGrammar extends Base
{
    use CompilesExpressions;
}
