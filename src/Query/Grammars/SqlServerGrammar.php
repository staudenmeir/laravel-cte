<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesSqlServerExpressions;

class SqlServerGrammar extends Base
{
    use CompilesSqlServerExpressions;
}
