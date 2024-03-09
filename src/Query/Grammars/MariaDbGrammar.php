<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Query\Grammars\MariaDbGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesMySqlExpressions;

class MariaDbGrammar extends Base
{
    use CompilesMySqlExpressions;
}
