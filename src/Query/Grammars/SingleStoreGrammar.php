<?php

namespace Staudenmeir\LaravelCte\Query\Grammars;

use Illuminate\Database\Connection;
use SingleStore\Laravel\Query\SingleStoreQueryGrammar;
use Staudenmeir\LaravelCte\Query\Grammars\Traits\CompilesMySqlExpressions;

class SingleStoreGrammar extends SingleStoreQueryGrammar implements ExpressionGrammar
{
    use CompilesMySqlExpressions;

    /** @inheritDoc */
    public function __construct(Connection $connection, $ignoreOrderByInDeletes, $ignoreOrderByInUpdates)
    {
        parent::__construct($connection, $ignoreOrderByInDeletes, $ignoreOrderByInUpdates);

        array_unshift($this->selectComponents, 'expressions');

        $this->selectComponents[] = 'recursionLimit';
    }
}
