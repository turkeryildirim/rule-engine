<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * The absolute value of a number.
 */
class Absolute extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand] = $this->getOperands();

        return new Value(\abs(Coerce::number($operand->prepareValue($context)->getValue())));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
