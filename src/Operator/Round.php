<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * A number rounded to the given precision (half away from zero). Precision 0 or less gives an int.
 */
class Round extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand, $precision] = $this->getOperands();
        $precision = Coerce::number($precision->prepareValue($context)->getValue());
        if (!\is_int($precision)) {
            throw new InvalidOperandException('round: precision must be an integer');
        }
        $rounded = \round(Coerce::number($operand->prepareValue($context)->getValue()), $precision);

        return new Value($precision <= 0 ? (int) $rounded : $rounded);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
