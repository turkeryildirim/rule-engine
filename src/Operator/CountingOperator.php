<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Proposition;

/**
 * Compares how many of its propositions hold with a fixed count.
 */
abstract class CountingOperator extends LogicalOperator
{
    /**
     * @param list<Proposition> $props
     *
     * @throws InvalidOperandException if the count is negative
     */
    public function __construct(private readonly int $count, array $props = [])
    {
        if ($count < 0) {
            throw new InvalidOperandException(\sprintf('%s: count must not be negative, %d given', static::class, $count));
        }

        parent::__construct($props);
    }

    public function getCount(): int
    {
        return $this->count;
    }

    #[\Override]
    public function evaluate(Context $context): bool
    {
        $holding = \count(\array_filter($this->getOperands(), static fn (Proposition $operand): bool => $operand->evaluate($context)));

        return $this->accepts($holding, $this->count);
    }

    abstract protected function accepts(int $holding, int $count): bool;

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Multiple;
    }
}
