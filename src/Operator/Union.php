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

namespace Ruler\Operator;

use Ruler\Context;
use Ruler\Set;
use Ruler\Value;
use Ruler\VariableOperand;

/**
 * A Set Union Operator.
 *
 * @author Jordan Raub <jordan@raub.me>
 */
class Union extends VariableOperator implements VariableOperand
{
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        $values = \array_map(static fn (VariableOperand $operand): Value => $operand->prepareValue($context), $this->getOperands());

        return new Set([])->union(...$values);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
