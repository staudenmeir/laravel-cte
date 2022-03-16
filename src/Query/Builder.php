<?php

namespace Staudenmeir\LaravelCte\Query;

use Illuminate\Database\Query\Builder as Base;
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

class Builder extends Base
{
    use BuildsExpressionQueries;
}
