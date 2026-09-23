<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * A custom value-producing operator that is not a VariableOperator subclass.
 */
final readonly class PlusOne implements VariableOperand
{
    public function __construct(private VariableOperand $operand)
    {
    }

    #[\Override]
    public function prepareValue(Context $context): Value
    {
        return new Value($this->operand->prepareValue($context)->add(new Value(1)));
    }
}
