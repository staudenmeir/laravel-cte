<?php

namespace Staudenmeir\LaravelCte\Query;

use SingleStore\Laravel\Query\SingleStoreQueryBuilder;
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

class SingleStoreBuilder extends SingleStoreQueryBuilder
{
    use BuildsExpressionQueries;
}
