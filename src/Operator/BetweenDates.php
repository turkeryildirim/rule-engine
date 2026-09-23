<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;

/**
 * True when start <= date <= end (inclusive).
 */
class BetweenDates extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$date, $start, $end] = $this->getOperands();
        $date = Coerce::date($date->prepareValue($context)->getValue());

        return Coerce::date($start->prepareValue($context)->getValue()) <= $date && $date <= Coerce::date($end->prepareValue($context)->getValue());
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Ternary;
    }
}
