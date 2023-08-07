<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use SingleStore\Laravel\Query\Grammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesMySqlExpressions;

class SingleStoreGrammar extends Base
{
    use CompilesMySqlExpressions;
}
