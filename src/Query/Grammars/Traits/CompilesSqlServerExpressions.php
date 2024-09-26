<?php

namespace Staudenmeir\LaravelCte\Query\Grammars\Traits;

use Illuminate\Database\Query\Builder;

trait CompilesSqlServerExpressions
{
    use CompilesExpressions {
        compileSelect as compileSelectParent;
    }

    /** @inheritDoc */
    public function compileSelect(Builder $query)
    {
        if ($query->offset && empty($query->orders)) {
            $query->orders[] = ['sql' => '(SELECT 0)'];
        }

        return $this->compileSelectParent($query);
    }

    /** @inheritDoc */
    protected function recursiveKeyword(array $expressions)
    {
        return '';
    }
}
