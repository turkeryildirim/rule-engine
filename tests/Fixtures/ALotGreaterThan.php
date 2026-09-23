<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Cardinality;
use D6N\RuleEngine\Operator\VariableOperator;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\Value;

/**
 * An EqualTo comparison operator.
 */
class ALotGreaterThan extends VariableOperator implements Proposition
{
    /**
     * Evaluate whether the given variables are equal in the current Context.
     *
     * @param Context $context Context with which to evaluate this ComparisonOperator
     */
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$left, $right] = $this->getOperands();
        $tenTimesRight = new Value($right->prepareValue($context)->multiply(new Value(10)));

        return $left->prepareValue($context)->greaterThan($tenTimesRight);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
