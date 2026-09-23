<?php

declare(strict_types=1);

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace D6N\RuleEngine;

/**
 * Ruler Context.
 *
 * The Context contains facts with which to evaluate a Rule or other Proposition.
 *
 * Derived from Pimple, by Fabien Potencier:
 *
 * https://github.com/fabpot/Pimple
 *
 * @author Fabien Potencier
 * @author Justin Hileman <justin@justinhileman.info>
 *
 * @implements \ArrayAccess<mixed, mixed>
 */
class Context implements \ArrayAccess
{
    /** @var array<array-key, true> */
    private array $keys = [];

    /** @var array<array-key, mixed> */
    private array $values = [];

    /** @var array<array-key, true> */
    private array $frozen = [];

    /** @var array<array-key, mixed> */
    private array $raw = [];

    /** @var \SplObjectStorage<object, null> */
    private readonly \SplObjectStorage $shared;

    /** @var \SplObjectStorage<object, null> */
    private readonly \SplObjectStorage $protected;

    /**
     * Context constructor.
     *
     * Optionally, bootstrap the context by passing an array of fact names and
     * values.
     *
     * @param array<array-key, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->shared = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Check if a fact is defined.
     *
     * @param mixed $name The unique name for the fact
     *
     * @phpstan-assert-if-true array-key $name
     */
    #[\Override]
    public function offsetExists(mixed $name): bool
    {
        return (\is_string($name) || \is_int($name)) && isset($this->keys[$name]);
    }

    /**
     * Get the value of a fact.
     *
     * @param mixed $name The unique name for the fact
     *
     * @return mixed The resolved value of the fact
     *
     * @throws \InvalidArgumentException if the name is not defined
     */
    #[\Override]
    public function offsetGet(mixed $name): mixed
    {
        $name = $this->definedName($name);
        $value = $this->values[$name];

        // Frozen facts, plain values and protected callables are returned as-is
        if (isset($this->frozen[$name]) || !$this->isCallable($value) || $this->protected->offsetExists($value)) {
            return $value;
        }

        // Shared facts are resolved once, then frozen
        if ($this->shared->offsetExists($value)) {
            $this->frozen[$name] = true;
            $this->raw[$name] = $value;

            return $this->values[$name] = $value($this);
        }

        return $value($this);
    }

    /**
     * Set a fact name and value.
     *
     * A fact will be lazily evaluated if it is a Closure or invokable object.
     * To define a fact as a literal callable, use Context::protect.
     *
     * @param mixed $name  The unique name for the fact
     * @param mixed $value The value or a closure to lazily define the value
     *
     * @throws \InvalidArgumentException if the name is not a string or an integer
     * @throws \RuntimeException         if a frozen fact is overridden
     */
    #[\Override]
    public function offsetSet(mixed $name, mixed $value): void
    {
        if (!\is_string($name) && !\is_int($name)) {
            throw new \InvalidArgumentException('Fact names must be strings or integers.');
        }

        if (isset($this->frozen[$name])) {
            throw new \RuntimeException(\sprintf('Cannot override frozen fact "%s".', $name));
        }

        $this->keys[$name] = true;
        $this->values[$name] = $value;
    }

    /**
     * Unset a fact.
     *
     * @param mixed $name The unique name for the fact
     */
    #[\Override]
    public function offsetUnset(mixed $name): void
    {
        if (!$this->offsetExists($name)) {
            return;
        }

        $value = $this->values[$name];
        if (\is_object($value)) {
            $this->shared->offsetUnset($value);
            $this->protected->offsetUnset($value);
        }

        unset($this->keys[$name], $this->values[$name], $this->frozen[$name], $this->raw[$name]);
    }

    /**
     * Define a fact as "shared". This lazily evaluates and stores the result
     * of the callable for the scope of this Context instance.
     *
     * @param mixed $callable A Closure or invokable object to share
     *
     * @return callable&object The passed callable
     *
     * @throws \InvalidArgumentException if the callable is not a Closure or invokable object
     */
    public function share(mixed $callable): object
    {
        if (!$this->isCallable($callable)) {
            throw new \InvalidArgumentException('Value is not a Closure or invokable object.');
        }

        $this->shared->offsetSet($callable);

        return $callable;
    }

    /**
     * Protect a callable from being interpreted as a lazy fact definition.
     * This is useful when you want to store a callable as the literal value of
     * a fact.
     *
     * @param mixed $callable A Closure or invokable object to protect from being evaluated
     *
     * @return callable&object The passed callable
     *
     * @throws \InvalidArgumentException if the callable is not a Closure or invokable object
     */
    public function protect(mixed $callable): object
    {
        if (!$this->isCallable($callable)) {
            throw new \InvalidArgumentException('Callable is not a Closure or invokable object.');
        }

        $this->protected->offsetSet($callable);

        return $callable;
    }

    /**
     * Get a fact or the closure defining a fact.
     *
     * @param array-key $name The unique name for the fact
     *
     * @return mixed The value of the fact or the closure defining the fact
     *
     * @throws \InvalidArgumentException if the name is not defined
     */
    public function raw(mixed $name): mixed
    {
        $name = $this->definedName($name);

        return isset($this->frozen[$name]) ? $this->raw[$name] : $this->values[$name];
    }

    /**
     * Get all defined fact names.
     *
     * @return list<array-key>
     */
    public function keys(): array
    {
        return \array_keys($this->keys);
    }

    /**
     * Check whether a value is a Closure or invokable object.
     *
     * @phpstan-assert-if-true callable&object $callable
     */
    protected function isCallable(mixed $callable): bool
    {
        return \is_object($callable) && \is_callable($callable);
    }

    /**
     * @return array-key
     *
     * @throws \InvalidArgumentException if the name is not defined
     */
    private function definedName(mixed $name): string|int
    {
        if (!$this->offsetExists($name)) {
            throw new \InvalidArgumentException(\sprintf('Fact "%s" is not defined.', \is_scalar($name) ? $name : \get_debug_type($name)));
        }

        return $name;
    }
}
