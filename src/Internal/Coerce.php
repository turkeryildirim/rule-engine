<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Internal;

use D6N\RuleEngine\Exception\ArithmeticException;
use D6N\RuleEngine\Exception\InvalidOperandException;

/**
 * Converts operand values into the types operators work with, and throws a
 * library exception when that is not possible.
 *
 * @internal
 */
final class Coerce
{
    /**
     * Strings pass through; ints, floats and Stringable objects are converted; null stays null.
     *
     * @throws InvalidOperandException if the value cannot be used as a string
     */
    public static function string(mixed $value): ?string
    {
        return match (true) {
            null === $value, \is_string($value)                               => $value,
            \is_int($value), \is_float($value), $value instanceof \Stringable => (string) $value,
            default                                                           => throw new InvalidOperandException('String operations: values must be strings'),
        };
    }

    /**
     * @throws ArithmeticException if the value is not a number or a numeric string
     */
    public static function number(mixed $value): int|float
    {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        if (\is_string($value) && \is_numeric($value)) {
            return +$value;
        }

        throw new ArithmeticException('Arithmetic: values must be numeric');
    }

    /**
     * Arrays become their values, null becomes an empty list, anything else a one-item list.
     *
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        return match (true) {
            null === $value   => [],
            \is_array($value) => \array_values($value),
            default           => [$value],
        };
    }

    /**
     * Accepts DateTimeInterface objects, Unix timestamps and date strings.
     *
     * @throws InvalidOperandException if the value is not a date
     */
    public static function date(mixed $value): \DateTimeImmutable
    {
        try {
            return match (true) {
                $value instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($value),
                \is_int($value)                      => new \DateTimeImmutable('@'.$value),
                \is_string($value)                   => new \DateTimeImmutable($value),
                default                              => throw new InvalidOperandException(\sprintf('Date operations: expected a date, %s given', \get_debug_type($value))),
            };
        } catch (\DateMalformedStringException $e) {
            throw new InvalidOperandException($e->getMessage(), previous: $e);
        }
    }

    /**
     * Accepts DateInterval objects and relative strings such as "7 days".
     *
     * @throws InvalidOperandException if the value is not an interval
     */
    public static function interval(mixed $value): \DateInterval
    {
        if ($value instanceof \DateInterval) {
            return $value;
        }

        if (!\is_string($value)) {
            throw new InvalidOperandException(\sprintf('Date operations: expected an interval, %s given', \get_debug_type($value)));
        }

        try {
            return \DateInterval::createFromDateString($value);
        } catch (\DateMalformedIntervalStringException $e) {
            throw new InvalidOperandException($e->getMessage(), previous: $e);
        }
    }
}
