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

use D6N\RuleEngine\Exception\InvalidNameException;
use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator\After;
use D6N\RuleEngine\Operator\Before;
use D6N\RuleEngine\Operator\Between;
use D6N\RuleEngine\Operator\BetweenDates;
use D6N\RuleEngine\Operator\ContainsAll;
use D6N\RuleEngine\Operator\ContainsAny;
use D6N\RuleEngine\Operator\ContainsSubset;
use D6N\RuleEngine\Operator\DoesNotContainSubset;
use D6N\RuleEngine\Operator\EndsWith;
use D6N\RuleEngine\Operator\EndsWithAny;
use D6N\RuleEngine\Operator\EndsWithInsensitive;
use D6N\RuleEngine\Operator\EqualTo;
use D6N\RuleEngine\Operator\GreaterThan;
use D6N\RuleEngine\Operator\GreaterThanOrEqualTo;
use D6N\RuleEngine\Operator\In;
use D6N\RuleEngine\Operator\IsArray;
use D6N\RuleEngine\Operator\IsBool;
use D6N\RuleEngine\Operator\IsEmpty;
use D6N\RuleEngine\Operator\IsNull;
use D6N\RuleEngine\Operator\IsNumeric;
use D6N\RuleEngine\Operator\IsString;
use D6N\RuleEngine\Operator\LessThan;
use D6N\RuleEngine\Operator\LessThanOrEqualTo;
use D6N\RuleEngine\Operator\Matches;
use D6N\RuleEngine\Operator\NotEqualTo;
use D6N\RuleEngine\Operator\NotIn;
use D6N\RuleEngine\Operator\NotSameAs;
use D6N\RuleEngine\Operator\OlderThan;
use D6N\RuleEngine\Operator\SameAs;
use D6N\RuleEngine\Operator\SetContains;
use D6N\RuleEngine\Operator\SetDoesNotContain;
use D6N\RuleEngine\Operator\StartsWith;
use D6N\RuleEngine\Operator\StartsWithAny;
use D6N\RuleEngine\Operator\StartsWithInsensitive;
use D6N\RuleEngine\Operator\StringContains;
use D6N\RuleEngine\Operator\StringContainsInsensitive;
use D6N\RuleEngine\Operator\StringDoesNotContain;
use D6N\RuleEngine\Operator\StringDoesNotContainInsensitive;
use D6N\RuleEngine\Operator\WithinLast;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Variable as BaseVariable;
use D6N\RuleEngine\VariableOperand;

/**
 * A propositional Variable with a fluent interface.
 *
 * Variables are placeholders in Propositions and Comparison Operators. During
 * evaluation, they are replaced with terminal Values, either from the Variable
 * default or from the current Context.
 *
 * Every operator known to the RuleBuilder's OperatorRegistry can be called as
 * a method; its arguments become the remaining operands. Operators that answer
 * true or false are returned as-is; operators that produce a value are wrapped
 * in a new Variable so that calls can be chained.
 *
 * Comparison:
 *
 * @method EqualTo              equalTo(mixed $value)
 * @method NotEqualTo           notEqualTo(mixed $value)
 * @method SameAs               sameAs(mixed $value)
 * @method NotSameAs            notSameAs(mixed $value)
 * @method GreaterThan          greaterThan(mixed $value)
 * @method GreaterThanOrEqualTo greaterThanOrEqualTo(mixed $value)
 * @method LessThan             lessThan(mixed $value)
 * @method LessThanOrEqualTo    lessThanOrEqualTo(mixed $value)
 * @method Between              between(mixed $min, mixed $max)
 * @method In                   in(mixed $list)
 * @method NotIn                notIn(mixed $list)
 *
 * Strings:
 * @method StringContains                  stringContains(mixed $value)
 * @method StringDoesNotContain            stringDoesNotContain(mixed $value)
 * @method StringContainsInsensitive       stringContainsInsensitive(mixed $value)
 * @method StringDoesNotContainInsensitive stringDoesNotContainInsensitive(mixed $value)
 * @method StartsWith                      startsWith(mixed $value)
 * @method StartsWithInsensitive           startsWithInsensitive(mixed $value)
 * @method EndsWith                        endsWith(mixed $value)
 * @method EndsWithInsensitive             endsWithInsensitive(mixed $value)
 * @method Matches                         matches(mixed $pattern)
 * @method ContainsAny                     containsAny(mixed $strings)
 * @method ContainsAll                     containsAll(mixed $strings)
 * @method StartsWithAny                   startsWithAny(mixed $prefixes)
 * @method EndsWithAny                     endsWithAny(mixed $suffixes)
 * @method self                            length()
 *
 * Math:
 * @method self add(mixed $value)
 * @method self subtract(mixed $value)
 * @method self multiply(mixed $value)
 * @method self divide(mixed $value)
 * @method self modulo(mixed $value)
 * @method self exponentiate(mixed $value)
 * @method self negate()
 * @method self ceil()
 * @method self floor()
 * @method self abs()
 * @method self round(mixed $precision)
 * @method self sum()
 * @method self avg()
 * @method self count()
 *
 * Sets:
 * @method self                 union(mixed ...$values)
 * @method self                 intersect(mixed ...$values)
 * @method self                 complement(mixed ...$values)
 * @method self                 symmetricDifference(mixed $value)
 * @method self                 min()
 * @method self                 max()
 * @method SetContains          setContains(mixed $value)
 * @method SetDoesNotContain    setDoesNotContain(mixed $value)
 * @method ContainsSubset       containsSubset(mixed $value)
 * @method DoesNotContainSubset doesNotContainSubset(mixed $value)
 *
 * Types:
 * @method IsNull    isNull()
 * @method IsEmpty   isEmpty()
 * @method IsString  isString()
 * @method IsNumeric isNumeric()
 * @method IsArray   isArray()
 * @method IsBool    isBool()
 *
 * Dates (see Context::now() for "now"):
 * @method Before       before(mixed $date)
 * @method After        after(mixed $date)
 * @method BetweenDates betweenDates(mixed $start, mixed $end)
 * @method WithinLast   withinLast(mixed $interval)
 * @method OlderThan    olderThan(mixed $interval)
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
     * @param string|null $name  Variable name (default: null)
     * @param mixed       $value Default Variable value (default: null)
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
     * @param mixed $name Property name
     */
    #[\Override]
    public function offsetExists(mixed $name): bool
    {
        return \is_string($name) && isset($this->properties[$name]);
    }

    /**
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
     * Set the default value of a VariableProperty.
     *
     * @param mixed $name  Property name
     * @param mixed $value The default VariableProperty value
     */
    #[\Override]
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->getProperty(self::name($name))->setValue($value);
    }

    /**
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
     * Apply a built-in or registered operator, with this Variable as its first operand.
     *
     * @see RuleBuilder::registerOperatorNamespace
     *
     * @param array<mixed> $args the remaining operands; plain values are wrapped in Variables
     *
     * @return Proposition|self the operator, or a Variable wrapping it if it produces a value
     *
     * @throws UnknownOperatorException if no operator is registered under the name
     */
    public function __call(string $name, array $args): Proposition|self
    {
        $class = $this->ruleBuilder->findOperator($name);
        $operands = \array_map(
            static fn (mixed $arg): BaseVariable => $arg instanceof BaseVariable ? $arg : new BaseVariable(null, $arg),
            \array_values($args),
        );
        $op = new $class($this, ...$operands);

        return $op instanceof VariableOperand ? new self($this->ruleBuilder, null, $op) : $op;
    }

    /**
     * @throws InvalidNameException if the name is not a string
     */
    private static function name(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new InvalidNameException(\sprintf('VariableProperty names must be strings, %s given.', \get_debug_type($name)));
        }

        return $name;
    }
}
