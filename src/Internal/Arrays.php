<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Internal;

/**
 * Short-circuiting array predicates (array_any()/array_all() need PHP 8.4).
 *
 * @internal
 */
final class Arrays
{
    /**
     * @template T
     *
     * @param array<array-key, T> $values
     * @param callable(T): bool   $predicate
     */
    public static function any(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if ($predicate($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template T
     *
     * @param array<array-key, T> $values
     * @param callable(T): bool   $predicate
     */
    public static function all(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if (!$predicate($value)) {
                return false;
            }
        }

        return true;
    }
}
