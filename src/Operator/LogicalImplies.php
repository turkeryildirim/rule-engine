<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;

/**
 * Material implication: false only when the first proposition holds and the second does not.
 */
class LogicalImplies extends LogicalOperator
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$if, $then] = $this->getOperands();

        return !$if->evaluate($context) || $then->evaluate($context);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
