<?php

namespace Staudenmeir\LaravelCte\Query;

use SingleStore\Laravel\Query\Builder as Base;
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

class SingleStoreBuilder extends Base
{
    use BuildsExpressionQueries;
}
