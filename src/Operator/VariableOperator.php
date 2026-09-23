<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Exception\OperandCountException;
use D6N\RuleEngine\Operator as BaseOperator;
use D6N\RuleEngine\VariableOperand;

/**
 * @extends BaseOperator<VariableOperand>
 */
abstract class VariableOperator extends BaseOperator
{
    #[\Override]
    public function addOperand(mixed $operand): void
    {
        $this->addVariable($operand);
    }

    /**
     * @throws OperandCountException if the operator cannot accept another operand
     */
    public function addVariable(VariableOperand $operand): void
    {
        $this->pushOperand($operand);
    }
}
