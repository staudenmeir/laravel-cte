<?php

namespace Staudenmeir\LaravelCte\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class User extends Model
{
    use QueriesExpressions;
}
