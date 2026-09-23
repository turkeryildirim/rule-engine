<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Proposition;

/**
 * True when min <= value <= max (inclusive), using PHP comparison.
 */
class Between extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$value, $min, $max] = $this->getOperands();
        $value = $value->prepareValue($context);

        return !$value->lessThan($min->prepareValue($context)) && !$value->greaterThan($max->prepareValue($context));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Ternary;
    }
}
