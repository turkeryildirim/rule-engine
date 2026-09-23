<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * The number of items in a list (duplicates included); a single value counts as 1, null as 0.
 */
class Count extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        [$operand] = $this->getOperands();
        $value = $operand->prepareValue($context)->getValue();

        return new Value($value instanceof \Countable ? \count($value) : \count(Coerce::list($value)));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
