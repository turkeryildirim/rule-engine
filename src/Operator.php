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

namespace Ruler;

use Ruler\Operator\Cardinality;

/**
 * @template TOperand of Proposition|VariableOperand
 *
 * @author Jordan Raub <jordan@raub.me>
 */
abstract class Operator
{
    /** @var list<TOperand> */
    protected array $operands = [];

    /**
     * @param TOperand ...$operands
     */
    public function __construct(mixed ...$operands)
    {
        foreach ($operands as $operand) {
            $this->addOperand($operand);
        }
    }

    /**
     * @return list<TOperand>
     *
     * @throws \LogicException if the operand count does not match the operator's cardinality
     */
    public function getOperands(): array
    {
        $cardinality = $this->getOperandCardinality();

        if (!$cardinality->isSatisfiedBy(\count($this->operands))) {
            throw new \LogicException(\sprintf('%s takes %s, %d given', static::class, $cardinality->describe(), \count($this->operands)));
        }

        return $this->operands;
    }

    /**
     * @param TOperand $operand
     */
    abstract public function addOperand(mixed $operand): void;

    abstract protected function getOperandCardinality(): Cardinality;

    /**
     * @param TOperand $operand
     *
     * @throws \LogicException if the operator cannot accept another operand
     */
    protected function pushOperand(Proposition|VariableOperand $operand): void
    {
        $cardinality = $this->getOperandCardinality();

        if (!$cardinality->acceptsAnother(\count($this->operands))) {
            throw new \LogicException(\sprintf('%s takes %s', static::class, $cardinality->describe()));
        }

        $this->operands[] = $operand;
    }
}
