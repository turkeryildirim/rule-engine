<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * A Set Intersection Operator.
 */
class Intersect extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        $operands = $this->getOperands();
        $rest = \array_map(static fn (VariableOperand $operand): Value => $operand->prepareValue($context), \array_slice($operands, 1));

        return $operands[0]->prepareValue($context)->getSet()->intersect(...$rest);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
