<?php

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Operator as BaseOperator;
use D6N\RuleEngine\Proposition;

/**
 * @extends BaseOperator<Proposition>
 *
 * @author Jordan Raub <jordan@raub.me>
 */
abstract class PropositionOperator extends BaseOperator
{
    #[\Override]
    public function addOperand(mixed $operand): void
    {
        $this->addProposition($operand);
    }

    /**
     * @throws \LogicException if the operator cannot accept another operand
     */
    public function addProposition(Proposition $operand): void
    {
        $this->pushOperand($operand);
    }
}
