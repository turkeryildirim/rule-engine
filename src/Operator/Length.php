<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * The number of characters (UTF-8) in a string, or of items in an array or Countable. Null has length 0.
 */
class Length extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand] = $this->getOperands();
        $value = $operand->prepareValue($context)->getValue();

        return new Value(match (true) {
            null === $value                                 => 0,
            \is_array($value), $value instanceof \Countable => \count($value),
            default                                         => \mb_strlen((string) Coerce::string($value)),
        });
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
