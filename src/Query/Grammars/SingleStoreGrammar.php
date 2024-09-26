<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use SingleStore\Laravel\Query\Grammar;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesMySqlExpressions;

class SingleStoreGrammar extends Grammar implements ExpressionGrammar
{
    use CompilesMySqlExpressions;
}
