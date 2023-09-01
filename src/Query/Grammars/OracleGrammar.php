<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesOracleExpressions;
use Yajra\Oci8\Query\Grammars\OracleGrammar as Base;

class OracleGrammar extends Base
{
    use CompilesOracleExpressions;
}
