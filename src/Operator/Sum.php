<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * The sum of a list of numbers; 0 for an empty list.
 */
class Sum extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand] = $this->getOperands();

        return new Value(\array_sum(\array_map(Coerce::number(...), Coerce::list($operand->prepareValue($context)->getValue()))));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
