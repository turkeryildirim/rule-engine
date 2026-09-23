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
 * A Complement Set Operator.
 *
 * @author Jordan Raub <jordan@raub.me>
 */
class Complement extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        $operands = $this->getOperands();
        $rest = \array_map(static fn (VariableOperand $operand): Value => $operand->prepareValue($context), \array_slice($operands, 1));

        return $operands[0]->prepareValue($context)->getSet()->complement(...$rest);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
