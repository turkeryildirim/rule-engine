<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;

/**
 * True when the date is strictly after the other date.
 */
class After extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$date, $other] = $this->getOperands();

        return Coerce::date($date->prepareValue($context)->getValue()) > Coerce::date($other->prepareValue($context)->getValue());
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
