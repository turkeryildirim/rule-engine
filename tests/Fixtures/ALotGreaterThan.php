<?php

declare(strict_types=1);

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D6N\RuleEngine\Test\Fixtures;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Cardinality;
use D6N\RuleEngine\Operator\VariableOperator;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\Value;

/**
 * An EqualTo comparison operator.
 *
 * @author Justin Hileman <justin@shopopensky.com>
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
