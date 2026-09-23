<?php

declare(strict_types=1);

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D6N\RuleEngine\RuleBuilder;

use D6N\RuleEngine\Operator\Addition;
use D6N\RuleEngine\Operator\Ceil;
use D6N\RuleEngine\Operator\Complement;
use D6N\RuleEngine\Operator\ContainsSubset;
use D6N\RuleEngine\Operator\Division;
use D6N\RuleEngine\Operator\DoesNotContainSubset;
use D6N\RuleEngine\Operator\EndsWith;
use D6N\RuleEngine\Operator\EndsWithInsensitive;
use D6N\RuleEngine\Operator\EqualTo;
use D6N\RuleEngine\Operator\Exponentiate;
use D6N\RuleEngine\Operator\Floor;
use D6N\RuleEngine\Operator\GreaterThan;
use D6N\RuleEngine\Operator\GreaterThanOrEqualTo;
use D6N\RuleEngine\Operator\Intersect;
use D6N\RuleEngine\Operator\LessThan;
use D6N\RuleEngine\Operator\LessThanOrEqualTo;
use D6N\RuleEngine\Operator\Max;
use D6N\RuleEngine\Operator\Min;
use D6N\RuleEngine\Operator\Modulo;
use D6N\RuleEngine\Operator\Multiplication;
use D6N\RuleEngine\Operator\Negation;
use D6N\RuleEngine\Operator\NotEqualTo;
use D6N\RuleEngine\Operator\NotSameAs;
use D6N\RuleEngine\Operator\SameAs;
use D6N\RuleEngine\Operator\SetContains;
use D6N\RuleEngine\Operator\SetDoesNotContain;
use D6N\RuleEngine\Operator\StartsWith;
use D6N\RuleEngine\Operator\StartsWithInsensitive;
use D6N\RuleEngine\Operator\StringContains;
use D6N\RuleEngine\Operator\StringContainsInsensitive;
use D6N\RuleEngine\Operator\StringDoesNotContain;
use D6N\RuleEngine\Operator\StringDoesNotContainInsensitive;
use D6N\RuleEngine\Operator\Subtraction;
use D6N\RuleEngine\Operator\SymmetricDifference;
use D6N\RuleEngine\Operator\Union;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Variable as BaseVariable;
use D6N\RuleEngine\VariableOperand;

/**
 * A propositional Variable.
 *
 * Variables are placeholders in Propositions and Comparison Operators. During
 * evaluation, they are replaced with terminal Values, either from the Variable
 * default or from the current Context.
 *
 * The RuleBuilder Variable extends the base Variable class with a fluent
 * interface for creating VariableProperties, Operators and Rules without all
 * kinds of awkward object instantiation.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 *
 * @implements \ArrayAccess<string, VariableProperty>
 */
class Variable extends BaseVariable implements \ArrayAccess
{
    /** @var array<string, VariableProperty> */
    private array $properties = [];

    /**
     * RuleBuilder Variable constructor.
     *
     * @param RuleBuilder $ruleBuilder
     * @param string|null $name        Variable name (default: null)
     * @param mixed       $value       Default Variable value (default: null)
     */
    public function __construct(private readonly RuleBuilder $ruleBuilder, ?string $name = null, mixed $value = null)
    {
        parent::__construct($name, $value);
    }

    /**
     * Get the RuleBuilder instance set on this Variable.
     */
    public function getRuleBuilder(): RuleBuilder
    {
        return $this->ruleBuilder;
    }

    /**
     * Get a VariableProperty for accessing methods, indexes and properties of
     * the current variable.
     *
     * @param string $name  Property name
     * @param mixed  $value The default VariableProperty value
     */
    public function getProperty(string $name, mixed $value = null): VariableProperty
    {
        return $this->properties[$name] ??= new VariableProperty($this, $name, $value);
    }

    /**
     * Fluent interface method for checking whether a VariableProperty has been defined.
     *
     * @param mixed $name Property name
     */
    #[\Override]
    public function offsetExists(mixed $name): bool
    {
        return \is_string($name) && isset($this->properties[$name]);
    }

    /**
     * Fluent interface method for creating or accessing VariableProperties.
     *
     * @see getProperty
     *
     * @param mixed $name Property name
     */
    #[\Override]
    public function offsetGet(mixed $name): VariableProperty
    {
        return $this->getProperty(self::name($name));
    }

    /**
     * Fluent interface method for setting default a VariableProperty value.
     *
     * @see setValue
     *
     * @param mixed $name  Property name
     * @param mixed $value The default Variable value
     */
    #[\Override]
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->getProperty(self::name($name))->setValue($value);
    }

    /**
     * Fluent interface method for removing a VariableProperty reference.
     *
     * @param mixed $name Property name
     */
    #[\Override]
    public function offsetUnset(mixed $name): void
    {
        if (\is_string($name)) {
            unset($this->properties[$name]);
        }
    }

    /**
     * Fluent interface helper to create a contains comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function stringContains(mixed $variable): StringContains
    {
        return new StringContains($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a contains comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function stringDoesNotContain(mixed $variable): StringDoesNotContain
    {
        return new StringDoesNotContain($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a insensitive contains comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function stringContainsInsensitive(mixed $variable): StringContainsInsensitive
    {
        return new StringContainsInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a insensitive does not contain comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function stringDoesNotContainInsensitive(mixed $variable): StringDoesNotContainInsensitive
    {
        return new StringDoesNotContainInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a GreaterThan comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function greaterThan(mixed $variable): GreaterThan
    {
        return new GreaterThan($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a GreaterThanOrEqualTo comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function greaterThanOrEqualTo(mixed $variable): GreaterThanOrEqualTo
    {
        return new GreaterThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a LessThan comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function lessThan(mixed $variable): LessThan
    {
        return new LessThan($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a LessThanOrEqualTo comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function lessThanOrEqualTo(mixed $variable): LessThanOrEqualTo
    {
        return new LessThanOrEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a EqualTo comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function equalTo(mixed $variable): EqualTo
    {
        return new EqualTo($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a NotEqualTo comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function notEqualTo(mixed $variable): NotEqualTo
    {
        return new NotEqualTo($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a SameAs comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function sameAs(mixed $variable): SameAs
    {
        return new SameAs($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a NotSameAs comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function notSameAs(mixed $variable): NotSameAs
    {
        return new NotSameAs($this, $this->asVariable($variable));
    }

    public function union(mixed ...$variables): self
    {
        return $this->wrap(new Union($this, ...$this->asVariables($variables)));
    }

    public function intersect(mixed ...$variables): self
    {
        return $this->wrap(new Intersect($this, ...$this->asVariables($variables)));
    }

    public function complement(mixed ...$variables): self
    {
        return $this->wrap(new Complement($this, ...$this->asVariables($variables)));
    }

    public function symmetricDifference(mixed $variable): self
    {
        return $this->wrap(new SymmetricDifference($this, $this->asVariable($variable)));
    }

    public function min(): self
    {
        return $this->wrap(new Min($this));
    }

    public function max(): self
    {
        return $this->wrap(new Max($this));
    }

    public function containsSubset(mixed $variable): ContainsSubset
    {
        return new ContainsSubset($this, $this->asVariable($variable));
    }

    public function doesNotContainSubset(mixed $variable): DoesNotContainSubset
    {
        return new DoesNotContainSubset($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a contains comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function setContains(mixed $variable): SetContains
    {
        return new SetContains($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a contains comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function setDoesNotContain(mixed $variable): SetDoesNotContain
    {
        return new SetDoesNotContain($this, $this->asVariable($variable));
    }

    public function add(mixed $variable): self
    {
        return $this->wrap(new Addition($this, $this->asVariable($variable)));
    }

    public function divide(mixed $variable): self
    {
        return $this->wrap(new Division($this, $this->asVariable($variable)));
    }

    public function modulo(mixed $variable): self
    {
        return $this->wrap(new Modulo($this, $this->asVariable($variable)));
    }

    public function multiply(mixed $variable): self
    {
        return $this->wrap(new Multiplication($this, $this->asVariable($variable)));
    }

    public function subtract(mixed $variable): self
    {
        return $this->wrap(new Subtraction($this, $this->asVariable($variable)));
    }

    public function negate(): self
    {
        return $this->wrap(new Negation($this));
    }

    public function ceil(): self
    {
        return $this->wrap(new Ceil($this));
    }

    public function floor(): self
    {
        return $this->wrap(new Floor($this));
    }

    public function exponentiate(mixed $variable): self
    {
        return $this->wrap(new Exponentiate($this, $this->asVariable($variable)));
    }

    /**
     * Private helper to retrieve a Variable instance for the given $variable.
     *
     * @param mixed $variable BaseVariable instance or value
     */
    private function asVariable(mixed $variable): BaseVariable
    {
        return ($variable instanceof BaseVariable) ? $variable : new BaseVariable(null, $variable);
    }

    /**
     * Private helper to wrap a VariableOperator in a Variable instance.
     */
    /**
     * @param array<mixed> $variables
     *
     * @return list<BaseVariable>
     */
    private function asVariables(array $variables): array
    {
        return \array_values(\array_map($this->asVariable(...), $variables));
    }

    private function wrap(VariableOperand $op): self
    {
        return new self($this->ruleBuilder, null, $op);
    }

    /**
     * Fluent interface helper to create a endsWith comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function endsWith(mixed $variable): EndsWith
    {
        return new EndsWith($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a endsWith insensitive comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function endsWithInsensitive(mixed $variable): EndsWithInsensitive
    {
        return new EndsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a startsWith comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function startsWith(mixed $variable): StartsWith
    {
        return new StartsWith($this, $this->asVariable($variable));
    }

    /**
     * Fluent interface helper to create a startsWith insensitive comparison operator.
     *
     * @param mixed $variable Right side of comparison operator
     */
    public function startsWithInsensitive(mixed $variable): StartsWithInsensitive
    {
        return new StartsWithInsensitive($this, $this->asVariable($variable));
    }

    /**
     * Magic method to apply operators registered with RuleBuilder.
     *
     * @see RuleBuilder::registerOperatorNamespace
     *
     * @param array<mixed> $args
     *
     * @return Proposition|self the operator, or a Variable wrapping it if it produces a value
     *
     * @throws \LogicException if operator is not registered
     */
    public function __call(string $name, array $args): Proposition|self
    {
        $class = $this->ruleBuilder->findOperator($name);
        $op = new $class($this, ...$this->asVariables($args));

        return $op instanceof VariableOperand ? $this->wrap($op) : $op;
    }

    /**
     * @throws \InvalidArgumentException if the name is not a string
     */
    private static function name(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException(\sprintf('%s names must be strings, %s given.', 'VariableProperty', \get_debug_type($name)));
        }

        return $name;
    }
}
