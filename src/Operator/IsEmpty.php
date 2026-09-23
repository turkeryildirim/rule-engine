<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Proposition;

/**
 * True for null, "", an empty array and an empty Countable. 0, "0" and false are not empty.
 */
class IsEmpty extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$operand] = $this->getOperands();
        $value = $operand->prepareValue($context)->getValue();

        return null === $value || '' === $value || [] === $value || ($value instanceof \Countable && 0 === \count($value));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
