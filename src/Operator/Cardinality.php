<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

/**
 * How many operands an Operator accepts.
 */
enum Cardinality
{
    /** Exactly one operand. */
    case Unary;

    /** Exactly two operands. */
    case Binary;

    /** Exactly three operands. */
    case Ternary;

    /** One or more operands. */
    case Multiple;

    /** Fewest operands, by case name. */
    private const array MINIMUM = ['Unary' => 1, 'Binary' => 2, 'Ternary' => 3, 'Multiple' => 1];

    /** Most operands, by case name. */
    private const array MAXIMUM = ['Unary' => 1, 'Binary' => 2, 'Ternary' => 3, 'Multiple' => \PHP_INT_MAX];

    /**
     * Whether an operator already holding $count operands can accept another one.
     */
    public function acceptsAnother(int $count): bool
    {
        return $count < self::MAXIMUM[$this->name];
    }

    /**
     * Whether $count operands is a complete, evaluable operand list.
     */
    public function isSatisfiedBy(int $count): bool
    {
        return $count >= self::MINIMUM[$this->name] && $count <= self::MAXIMUM[$this->name];
    }

    /**
     * E.g. "exactly 2 operands" or "at least 1 operand".
     */
    public function describe(): string
    {
        $minimum = self::MINIMUM[$this->name];

        return \sprintf(
            '%s %d operand%s',
            $minimum === self::MAXIMUM[$this->name] ? 'exactly' : 'at least',
            $minimum,
            1 === $minimum ? '' : 's',
        );
    }
}
