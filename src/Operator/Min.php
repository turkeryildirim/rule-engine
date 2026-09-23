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
use D6N\RuleEngine\Value;
use D6N\RuleEngine\VariableOperand;

/**
 * A set min operator.
 *
 * @author Jordan Raub <jordan@raub.me>
 */
class Min extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->getSet()->min());
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Unary;
    }
}
