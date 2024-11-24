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

    /**
     * Get the "recursive" keyword.
     *
     * @param list<array{name: string, query: string, columns: list<string|\Illuminate\Database\Query\Expression>|null,
     *        recursive: bool, materialized: bool|null,
     *        cycle: array{columns: list<string>, markColumn: string, pathColumn: string}|null}> $expressions
     * @return string
     */
    protected function recursiveKeyword(array $expressions)
    {
        return '';
    }
}
