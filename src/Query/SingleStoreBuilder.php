<?php

namespace Staudenmeir\LaravelCte\Query;

use SingleStore\Laravel\Query\Builder;
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

class SingleStoreBuilder extends Builder
{
    use BuildsExpressionQueries;
}
