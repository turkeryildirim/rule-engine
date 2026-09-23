<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Set;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * A Set Union Operator.
 */
class Union extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        $values = \array_map(static fn (VariableOperand $operand): Value => $operand->prepareValue($context), $this->getOperands());

        return new Set([])->union(...$values);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
