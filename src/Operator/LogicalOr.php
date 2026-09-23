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

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Proposition;

/**
 * A logical OR operator.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 */
class LogicalOr extends LogicalOperator
{
    /**
     * @param Context $context Context with which to evaluate this Proposition
     */
    #[\Override]
    public function evaluate(Context $context): bool
    {
        return \array_any($this->getOperands(), static fn (Proposition $operand): bool => $operand->evaluate($context));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
