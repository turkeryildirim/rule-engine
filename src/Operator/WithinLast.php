<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;

/**
 * True when the date lies in the given interval before now (inclusive), e.g. withinLast("7 days"). "Now" comes from the Context clock.
 */
class WithinLast extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$date, $interval] = $this->getOperands();
        $date = Coerce::date($date->prepareValue($context)->getValue());
        $now = $context->now();

        return $now->sub(Coerce::interval($interval->prepareValue($context)->getValue())) <= $date && $date <= $now;
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
