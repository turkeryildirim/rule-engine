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

namespace Ruler;

use Ruler\Operator\LogicalAnd;
use Ruler\Operator\LogicalNot;
use Ruler\Operator\LogicalOr;
use Ruler\Operator\LogicalXor;
use Ruler\RuleBuilder\Variable;

/**
 * RuleBuilder.
 *
 * The RuleBuilder provides a DSL and fluent interface for constructing
 * Rules.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 *
 * @implements \ArrayAccess<string, Variable>
 */
class RuleBuilder implements \ArrayAccess
{
    /** @var array<string, Variable> */
    private array $variables = [];
    /** @var array<string, true> */
    private array $operatorNamespaces = [];

    /**
     * Create a Rule with the given propositional condition.
     *
     * @param Proposition $condition Propositional condition for this Rule
     * @param callable    $action    Action (callable) to take upon successful Rule execution (default: null)
     */
    public function create(Proposition $condition, ?callable $action = null): Rule
    {
        return new Rule($condition, $action);
    }

    /**
     * Register an operator namespace.
     *
     * Note that, depending on your filesystem, operator namespaces are most likely case sensitive.
     */
    public function registerOperatorNamespace(string $namespace): self
    {
        $this->operatorNamespaces[$namespace] = true;

        return $this;
    }

    /**
     * Create a logical AND operator proposition.
     *
     * @param Proposition ...$props One or more Propositions
     */
    public function logicalAnd(Proposition ...$props): LogicalAnd
    {
        return new LogicalAnd(\array_values($props));
    }

    /**
     * Create a logical OR operator proposition.
     *
     * @param Proposition ...$props One or more Propositions
     */
    public function logicalOr(Proposition ...$props): LogicalOr
    {
        return new LogicalOr(\array_values($props));
    }

    /**
     * Create a logical NOT operator proposition.
     *
     * @param Proposition $prop Exactly one Proposition
     */
    public function logicalNot(Proposition $prop): LogicalNot
    {
        return new LogicalNot([$prop]);
    }

    /**
     * Create a logical XOR operator proposition.
     *
     * @param Proposition ...$props One or more Propositions
     */
    public function logicalXor(Proposition ...$props): LogicalXor
    {
        return new LogicalXor(\array_values($props));
    }

    /**
     * Check whether a Variable is already set.
     *
     * @param mixed $name The Variable name
     */
    #[\Override]
    public function offsetExists(mixed $name): bool
    {
        return \is_string($name) && isset($this->variables[$name]);
    }

    /**
     * Retrieve a Variable by name.
     *
     * @param mixed $name The Variable name
     */
    #[\Override]
    public function offsetGet(mixed $name): Variable
    {
        $name = self::name($name);

        return $this->variables[$name] ??= new Variable($this, $name);
    }

    /**
     * Set the default value of a Variable.
     *
     * @param mixed $name  The Variable name
     * @param mixed $value The Variable default value
     */
    #[\Override]
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->offsetGet($name)->setValue($value);
    }

    /**
     * Remove a defined Variable from the RuleBuilder.
     *
     * @param mixed $name The Variable name
     */
    #[\Override]
    public function offsetUnset(mixed $name): void
    {
        if (\is_string($name)) {
            unset($this->variables[$name]);
        }
    }

    /**
     * Find an operator in the registered operator namespaces.
     *
     * @return class-string<Proposition|VariableOperand>
     *
     * @throws \LogicException if a matching operator is not found
     */
    public function findOperator(string $name): string
    {
        $operator = \ucfirst($name);
        foreach (\array_keys($this->operatorNamespaces) as $namespace) {
            $class = $namespace.'\\'.$operator;
            if (\is_subclass_of($class, Proposition::class) || \is_subclass_of($class, VariableOperand::class)) {
                return $class;
            }
        }

        throw new \LogicException(\sprintf('Unknown operator: "%s"', $name));
    }

    /**
     * @throws \InvalidArgumentException if the name is not a string
     */
    private static function name(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException(\sprintf('%s names must be strings, %s given.', 'Variable', \get_debug_type($name)));
        }

        return $name;
    }
}
