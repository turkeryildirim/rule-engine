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

    /**
     * Whether an operator already holding $count operands can accept another one.
     */
    public function acceptsAnother(int $count): bool
    {
        return match ($this) {
            self::Unary    => $count < 1,
            self::Binary   => $count < 2,
            self::Ternary  => $count < 3,
            self::Multiple => true,
        };
    }

    /**
     * Whether $count operands is a complete, evaluable operand list.
     */
    public function isSatisfiedBy(int $count): bool
    {
        return match ($this) {
            self::Unary    => 1 === $count,
            self::Binary   => 2 === $count,
            self::Ternary  => 3 === $count,
            self::Multiple => $count > 0,
        };
    }

    public function describe(): string
    {
        return match ($this) {
            self::Unary    => 'exactly 1 operand',
            self::Binary   => 'exactly 2 operands',
            self::Ternary  => 'exactly 3 operands',
            self::Multiple => 'at least 1 operand',
        };
    }
}
