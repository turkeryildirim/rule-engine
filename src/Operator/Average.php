<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * The arithmetic mean of a list of numbers; null for an empty list.
 */
class Average extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand] = $this->getOperands();
        $numbers = \array_map(Coerce::number(...), Coerce::list($operand->prepareValue($context)->getValue()));

        return new Value([] === $numbers ? null : \array_sum($numbers) / \count($numbers));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
