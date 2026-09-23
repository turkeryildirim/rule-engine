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

/**
 * Resolves a named property of a parent value, shared by both VariableProperty classes.
 *
 * @internal
 */
final class PropertyResolver
{
    /**
     * If $parent is an object, look up, in order: a public method named $name
     * (__call is not consulted),
     * a public property that is set, then an ArrayAccess offset. If $parent is
     * an array, look up the $name key. Otherwise, or if nothing matches, return
     * $default.
     */
    public static function resolve(mixed $parent, string $name, mixed $default): Value
    {
        $value = $default;

        if (\is_object($parent) && !$parent instanceof \Closure) {
            $method = [$parent, $name];
            // method_exists: a real method, not __call; is_callable: public from here.
            if (\method_exists($parent, $name) && \is_callable($method)) {
                $value = $method();
            } elseif (isset($parent->{$name})) { // @phpstan-ignore property.dynamicName (looking up a property by name is the point; this also honours __isset/__get)
                $value = $parent->{$name}; // @phpstan-ignore property.dynamicName
            } elseif ($parent instanceof \ArrayAccess && $parent->offsetExists($name)) {
                $value = $parent->offsetGet($name);
            }
        } elseif (\is_array($parent) && \array_key_exists($name, $parent)) {
            $value = $parent[$name];
        }

        return $value instanceof Value ? $value : new Value($value);
    }
}
