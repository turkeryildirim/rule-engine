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

namespace D6N\RuleEngine;

use D6N\RuleEngine\Exception\ArithmeticException;
use D6N\RuleEngine\Exception\DivisionByZeroException;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Internal\Coerce;

/**
 * A Ruler Value.
 *
 * A Value represents a comparable terminal value. Variables and Comparison Operators
 * are resolved to Values by applying the current Context and the default Variable value.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 */
class Value implements \Stringable
{
    /**
     * Value constructor.
     *
     * A Value object is immutable, and is used by Variables for comparing their default
     * values or facts from the current Context.
     *
     * @param mixed $value Immutable value represented by this Value object
     */
    public function __construct(protected readonly mixed $value)
    {
    }

    #[\Override]
    public function __toString(): string
    {
        if (\is_object($this->value)) {
            return \spl_object_hash($this->value);
        }

        return \serialize($this->value);
    }

    /**
     * Get the underlying value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get a Set containing the underlying value.
     */
    public function getSet(): Set
    {
        return new Set($this->value);
    }

    /**
     * Strict equality comparison (===).
     *
     * @param Value $value Value object to compare against
     */
    public function equalTo(self $value): bool
    {
        return $this->value === $value->getValue();
    }

    /**
     * Loose equality comparison (==), using PHP 8 comparison semantics.
     *
     * @param Value $value Value object to compare against
     */
    public function sameAs(self $value): bool
    {
        return $this->value == $value->getValue(); // @phpstan-ignore equal.notAllowed (sameAs is loose by design)
    }

    /**
     * Contains comparison. A null value contains nothing.
     *
     * @param Value $value Value object to compare against
     *
     * @throws InvalidOperandException if either value is not a string (or null)
     */
    public function stringContains(self $value): bool
    {
        [$haystack, $needle] = self::stringOperands($this, $value);

        return null !== $haystack && null !== $needle && \str_contains($haystack, $needle);
    }

    /**
     * Case-insensitive contains comparison (Unicode, see Coerce::foldCase()). A null value contains nothing.
     *
     * @param Value $value Value object to compare against
     *
     * @throws InvalidOperandException if either value is not a string (or null)
     */
    public function stringContainsInsensitive(self $value): bool
    {
        [$haystack, $needle] = self::stringOperands($this, $value, true);

        return null !== $haystack && null !== $needle && \str_contains($haystack, $needle);
    }

    /**
     * Greater than comparison.
     *
     * @param Value $value Value object to compare against
     */
    public function greaterThan(self $value): bool
    {
        return $this->value > $value->getValue();
    }

    /**
     * Less than comparison.
     *
     * @param Value $value Value object to compare against
     */
    public function lessThan(self $value): bool
    {
        return $this->value < $value->getValue();
    }

    /**
     * @throws ArithmeticException if either value is not numeric
     */
    public function add(self $value): int|float
    {
        return Coerce::number($this->value) + Coerce::number($value->getValue());
    }

    /**
     * @throws ArithmeticException     if either value is not numeric
     * @throws DivisionByZeroException if the divisor is zero
     */
    public function divide(self $value): int|float
    {
        $dividend = Coerce::number($this->value);
        $divisor = self::nonZero(Coerce::number($value->getValue()));

        return $dividend / $divisor;
    }

    /**
     * Remainder of the division. Integer operands use %, anything else uses fmod().
     *
     * @throws ArithmeticException     if either value is not numeric
     * @throws DivisionByZeroException if the divisor is zero
     */
    public function modulo(self $value): int|float
    {
        $dividend = Coerce::number($this->value);
        $divisor = self::nonZero(Coerce::number($value->getValue()));

        if (\is_int($dividend) && \is_int($divisor)) {
            return $dividend % $divisor;
        }

        return \fmod($dividend, $divisor);
    }

    /**
     * @throws ArithmeticException if either value is not numeric
     */
    public function multiply(self $value): int|float
    {
        return Coerce::number($this->value) * Coerce::number($value->getValue());
    }

    /**
     * @throws ArithmeticException if either value is not numeric
     */
    public function subtract(self $value): int|float
    {
        return Coerce::number($this->value) - Coerce::number($value->getValue());
    }

    /**
     * @throws ArithmeticException if the value is not numeric
     */
    public function negate(): int|float
    {
        return -Coerce::number($this->value);
    }

    /**
     * @throws ArithmeticException if the value is not numeric
     */
    public function ceil(): int|float
    {
        return self::toIntIfExact(\ceil(Coerce::number($this->value)));
    }

    /**
     * @throws ArithmeticException if the value is not numeric
     */
    public function floor(): int|float
    {
        return self::toIntIfExact(\floor(Coerce::number($this->value)));
    }

    /**
     * @throws ArithmeticException     if either value is not numeric
     * @throws DivisionByZeroException if zero is raised to a negative power
     */
    public function exponentiate(self $value): int|float
    {
        $base = Coerce::number($this->value);
        $exponent = Coerce::number($value->getValue());

        if (self::isZero($base) && $exponent < 0) {
            throw new DivisionByZeroException('Division by zero');
        }

        return $base ** $exponent;
    }

    /**
     * Starts with comparison. An empty prefix or a null value never matches.
     *
     * @param Value $value       Value object to compare against
     * @param bool  $insensitive Ignore case, using Unicode folding and the Turkish i rule (see Coerce::foldCase())
     *
     * @throws InvalidOperandException if either value is not a string (or null)
     */
    public function startsWith(self $value, bool $insensitive = false): bool
    {
        [$haystack, $prefix] = self::stringOperands($this, $value, $insensitive);

        return null !== $haystack && null !== $prefix && '' !== $prefix && \str_starts_with($haystack, $prefix);
    }

    /**
     * Ends with comparison. An empty suffix or a null value never matches.
     *
     * @param Value $value       Value object to compare against
     * @param bool  $insensitive Ignore case, using Unicode folding and the Turkish i rule (see Coerce::foldCase())
     *
     * @throws InvalidOperandException if either value is not a string (or null)
     */
    public function endsWith(self $value, bool $insensitive = false): bool
    {
        [$haystack, $suffix] = self::stringOperands($this, $value, $insensitive);

        return null !== $haystack && null !== $suffix && '' !== $suffix && \str_ends_with($haystack, $suffix);
    }

    /**
     * @throws DivisionByZeroException if the divisor is zero
     */
    private static function nonZero(int|float $divisor): int|float
    {
        if (self::isZero($divisor)) {
            throw new DivisionByZeroException('Division by zero');
        }

        return $divisor;
    }

    private static function isZero(int|float $number): bool
    {
        return 0 === $number || 0.0 === $number;
    }

    private static function toIntIfExact(float $value): int|float
    {
        return $value >= \PHP_INT_MIN && $value <= \PHP_INT_MAX ? (int) $value : $value;
    }

    /**
     * Both values as strings (null stays null), case-folded when $insensitive.
     *
     * @return array{?string, ?string}
     *
     * @throws InvalidOperandException if either value cannot be used as a string
     */
    private static function stringOperands(self $left, self $right, bool $insensitive = false): array
    {
        $strings = [Coerce::string($left->getValue()), Coerce::string($right->getValue())];

        return $insensitive
            ? \array_map(static fn (?string $s): ?string => null === $s ? null : Coerce::foldCase($s), $strings)
            : $strings;
    }
}
