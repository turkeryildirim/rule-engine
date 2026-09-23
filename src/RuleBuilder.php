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

use D6N\RuleEngine\Exception\InvalidNameException;
use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator\AtLeast;
use D6N\RuleEngine\Operator\AtMost;
use D6N\RuleEngine\Operator\Exactly;
use D6N\RuleEngine\Operator\LogicalAnd;
use D6N\RuleEngine\Operator\LogicalImplies;
use D6N\RuleEngine\Operator\LogicalNand;
use D6N\RuleEngine\Operator\LogicalNor;
use D6N\RuleEngine\Operator\LogicalNot;
use D6N\RuleEngine\Operator\LogicalOr;
use D6N\RuleEngine\Operator\LogicalXor;
use D6N\RuleEngine\RuleBuilder\Variable;

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
    private readonly OperatorRegistry $operators;

    public function __construct(?OperatorRegistry $operators = null)
    {
        $this->operators = $operators ?? new OperatorRegistry();
    }

    /**
     * Create a Rule with the given propositional condition.
     *
     * @param Proposition   $condition Propositional condition for this Rule
     * @param callable|null $action    Called with the Context when the Rule is executed and its condition holds
     * @param string|null   $name      Identifies the Rule in error messages, explanations and exported JSON
     */
    public function create(Proposition $condition, ?callable $action = null, ?string $name = null): Rule
    {
        return new Rule($condition, $action, $name);
    }

    /**
     * Register an operator namespace.
     *
     * Note that, depending on your filesystem, operator namespaces are most likely case sensitive.
     */
    public function registerOperatorNamespace(string $namespace): self
    {
        $this->operators->registerNamespace($namespace);

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
     * Create a logical implication: false only when $if holds and $then does not.
     */
    public function logicalImplies(Proposition $if, Proposition $then): LogicalImplies
    {
        return new LogicalImplies([$if, $then]);
    }

    /**
     * Create a logical NAND: true unless every proposition holds.
     */
    public function logicalNand(Proposition ...$props): LogicalNand
    {
        return new LogicalNand(\array_values($props));
    }

    /**
     * Create a logical NOR: true when no proposition holds.
     */
    public function logicalNor(Proposition ...$props): LogicalNor
    {
        return new LogicalNor(\array_values($props));
    }

    /**
     * True when at least $count of the propositions hold.
     */
    public function atLeast(int $count, Proposition ...$props): AtLeast
    {
        return new AtLeast($count, \array_values($props));
    }

    /**
     * True when at most $count of the propositions hold.
     */
    public function atMost(int $count, Proposition ...$props): AtMost
    {
        return new AtMost($count, \array_values($props));
    }

    /**
     * True when exactly $count of the propositions hold.
     */
    public function exactly(int $count, Proposition ...$props): Exactly
    {
        return new Exactly($count, \array_values($props));
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
     * Find a built-in or registered operator by name.
     *
     * @return class-string<Proposition|VariableOperand>
     *
     * @throws UnknownOperatorException if a matching operator is not found
     */
    public function findOperator(string $name): string
    {
        return $this->operators->resolve($name);
    }

    public function getOperatorRegistry(): OperatorRegistry
    {
        return $this->operators;
    }

    /**
     * @throws InvalidNameException if the name is not a string
     */
    private static function name(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new InvalidNameException(\sprintf('%s names must be strings, %s given.', 'Variable', \get_debug_type($name)));
        }

        return $name;
    }
}
