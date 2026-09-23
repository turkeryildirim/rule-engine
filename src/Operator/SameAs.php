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
use D6N\RuleEngine\VariableOperand;

/**
 * A SameAs comparison operator.
 *
 * @author Christophe Sicard <sicard.christophe@gmail.com>
 */
class SameAs extends VariableOperator implements Proposition
{
    /**
     * @param Context $context Context with which to evaluate this Proposition
     */
    #[\Override]
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->sameAs($right->prepareValue($context));
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
