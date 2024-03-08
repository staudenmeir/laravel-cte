<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use HarryGulliford\Firebird\Query\Grammars\FirebirdGrammar as Base;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesFirebirdExpressions;

class FirebirdGrammar extends Base
{
    use CompilesFirebirdExpressions;
}
