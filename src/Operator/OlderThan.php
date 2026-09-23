<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;

/**
 * True when the date is at least the given interval before now, e.g. olderThan("18 years"). "Now" comes from the Context clock.
 */
class OlderThan extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$date, $interval] = $this->getOperands();

        return Coerce::date($date->prepareValue($context)->getValue()) <= $context->now()->sub(Coerce::interval($interval->prepareValue($context)->getValue()));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
