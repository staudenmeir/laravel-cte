<?php

namespace Staudenmeir\LaravelCte\Query;

use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;
use Yajra\Oci8\Query\OracleBuilder as Base;

class OracleBuilder extends Base
{
    use BuildsExpressionQueries;
}
