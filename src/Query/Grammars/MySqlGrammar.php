<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesMySqlExpressions;

class MySqlGrammar extends Base
{
    use CompilesMySqlExpressions;
}
