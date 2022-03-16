<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesSQLiteExpressions;

class SQLiteGrammar extends Base
{
    use CompilesSQLiteExpressions;
}
