<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Proposition;

/**
 * True unless every proposition holds.
 */
class LogicalNand extends LogicalOperator
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        return !\array_all($this->getOperands(), static fn (Proposition $operand): bool => $operand->evaluate($context));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
