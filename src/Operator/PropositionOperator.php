<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Exception\OperandCountException;
use D6N\RuleEngine\Operator as BaseOperator;
use D6N\RuleEngine\Proposition;

/**
 * @extends BaseOperator<Proposition>
 */
abstract class PropositionOperator extends BaseOperator
{
    #[\Override]
    public function addOperand(mixed $operand): void
    {
        $this->addProposition($operand);
    }

    /**
     * @throws OperandCountException if the operator cannot accept another operand
     */
    public function addProposition(Proposition $operand): void
    {
        $this->pushOperand($operand);
    }
}
