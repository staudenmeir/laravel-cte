<?php

namespace Staudenmeir\LaravelCte\Query;

use HarryGulliford\Firebird\Query\Builder as Base;
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

class FirebirdBuilder extends Base
{
    use BuildsExpressionQueries;
}
