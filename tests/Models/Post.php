<?php

namespace Staudenmeir\LaravelCte\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

class Post extends Model
{
    use QueriesExpressions;
}
