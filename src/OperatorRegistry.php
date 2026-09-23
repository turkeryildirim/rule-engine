<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator as Op;

/**
 * Maps operator names (as used by the fluent DSL and in exported JSON) to
 * operator classes, and back.
 *
 * Built-in operators are always available. Custom operators are found by
 * explicit registration, or by class name in a registered namespace:
 * "aLotGreaterThan" resolves to "App\Rules\ALotGreaterThan".
 */
final class OperatorRegistry
{
    /**
     * Built-in operators by name. RuleBuilder\Variable documents the fluent
     * ones with @method tags; a test keeps the two in sync.
     *
     * @var array<string, class-string<Proposition|VariableOperand>>
     */
    public const array BUILT_INS = [
        // Comparison
        'equalTo'              => Op\EqualTo::class,
        'notEqualTo'           => Op\NotEqualTo::class,
        'sameAs'               => Op\SameAs::class,
        'notSameAs'            => Op\NotSameAs::class,
        'greaterThan'          => Op\GreaterThan::class,
        'greaterThanOrEqualTo' => Op\GreaterThanOrEqualTo::class,
        'lessThan'             => Op\LessThan::class,
        'lessThanOrEqualTo'    => Op\LessThanOrEqualTo::class,
        'between'              => Op\Between::class,
        'in'                   => Op\In::class,
        'notIn'                => Op\NotIn::class,

        // Strings
        'stringContains'                  => Op\StringContains::class,
        'stringDoesNotContain'            => Op\StringDoesNotContain::class,
        'stringContainsInsensitive'       => Op\StringContainsInsensitive::class,
        'stringDoesNotContainInsensitive' => Op\StringDoesNotContainInsensitive::class,
        'startsWith'                      => Op\StartsWith::class,
        'startsWithInsensitive'           => Op\StartsWithInsensitive::class,
        'endsWith'                        => Op\EndsWith::class,
        'endsWithInsensitive'             => Op\EndsWithInsensitive::class,
        'matches'                         => Op\Matches::class,
        'containsAny'                     => Op\ContainsAny::class,
        'containsAll'                     => Op\ContainsAll::class,
        'startsWithAny'                   => Op\StartsWithAny::class,
        'endsWithAny'                     => Op\EndsWithAny::class,
        'length'                          => Op\Length::class,

        // Types
        'isNull'    => Op\IsNull::class,
        'isEmpty'   => Op\IsEmpty::class,
        'isString'  => Op\IsString::class,
        'isNumeric' => Op\IsNumeric::class,
        'isArray'   => Op\IsArray::class,
        'isBool'    => Op\IsBool::class,

        // Math
        'add'          => Op\Addition::class,
        'subtract'     => Op\Subtraction::class,
        'multiply'     => Op\Multiplication::class,
        'divide'       => Op\Division::class,
        'modulo'       => Op\Modulo::class,
        'exponentiate' => Op\Exponentiate::class,
        'negate'       => Op\Negation::class,
        'ceil'         => Op\Ceil::class,
        'floor'        => Op\Floor::class,
        'abs'          => Op\Absolute::class,
        'round'        => Op\Round::class,
        'sum'          => Op\Sum::class,
        'avg'          => Op\Average::class,
        'count'        => Op\Count::class,

        // Dates
        'before'       => Op\Before::class,
        'after'        => Op\After::class,
        'betweenDates' => Op\BetweenDates::class,
        'withinLast'   => Op\WithinLast::class,
        'olderThan'    => Op\OlderThan::class,

        // Sets
        'union'                => Op\Union::class,
        'intersect'            => Op\Intersect::class,
        'complement'           => Op\Complement::class,
        'symmetricDifference'  => Op\SymmetricDifference::class,
        'min'                  => Op\Min::class,
        'max'                  => Op\Max::class,
        'setContains'          => Op\SetContains::class,
        'setDoesNotContain'    => Op\SetDoesNotContain::class,
        'containsSubset'       => Op\ContainsSubset::class,
        'doesNotContainSubset' => Op\DoesNotContainSubset::class,

        // Logic (built with RuleBuilder, not the fluent Variable interface)
        'logicalAnd'     => Op\LogicalAnd::class,
        'logicalOr'      => Op\LogicalOr::class,
        'logicalNot'     => Op\LogicalNot::class,
        'logicalXor'     => Op\LogicalXor::class,
        'logicalImplies' => Op\LogicalImplies::class,
        'logicalNand'    => Op\LogicalNand::class,
        'logicalNor'     => Op\LogicalNor::class,
        'atLeast'        => Op\AtLeast::class,
        'atMost'         => Op\AtMost::class,
        'exactly'        => Op\Exactly::class,
    ];

    /** @var array<string, class-string<Proposition|VariableOperand>> */
    private array $operators = self::BUILT_INS;

    /** @var array<string, true> */
    private array $namespaces = [];

    /**
     * Register a custom operator under an explicit name.
     *
     * @param class-string $class
     *
     * @throws UnknownOperatorException if the class is not a Proposition or VariableOperand
     */
    public function register(string $name, string $class): void
    {
        $this->operators[$name] = self::operatorClass($class, $name);
    }

    /**
     * Resolve operators by class name in the given namespace.
     *
     * Note that, depending on your filesystem, operator namespaces are most likely case sensitive.
     */
    public function registerNamespace(string $namespace): void
    {
        $this->namespaces[\trim($namespace, '\\')] = true;
    }

    /**
     * @return class-string<Proposition|VariableOperand>
     *
     * @throws UnknownOperatorException if no operator is registered under the name
     */
    public function resolve(string $name): string
    {
        if (isset($this->operators[$name])) {
            return $this->operators[$name];
        }

        foreach (\array_keys($this->namespaces) as $namespace) {
            $class = $namespace.'\\'.\ucfirst($name);
            if (self::isOperator($class)) {
                return $class;
            }
        }

        throw new UnknownOperatorException(\sprintf('Unknown operator: "%s"', $name));
    }

    /**
     * The name under which an operator can be resolved again.
     *
     * @throws UnknownOperatorException if the operator cannot be resolved by any name
     */
    public function nameOf(Proposition|VariableOperand $operator): string
    {
        $name = \array_search($operator::class, $this->operators, true);
        if (\is_string($name)) {
            return $name;
        }

        $short = \lcfirst(new \ReflectionClass($operator)->getShortName());
        if ($this->resolves($short, $operator::class)) {
            return $short;
        }

        throw new UnknownOperatorException(\sprintf('Operator %s is not registered; register it or its namespace first.', $operator::class));
    }

    private function resolves(string $name, string $class): bool
    {
        try {
            return $this->resolve($name) === $class;
        } catch (UnknownOperatorException) {
            return false;
        }
    }

    /**
     * @phpstan-assert-if-true class-string<Proposition|VariableOperand> $class
     */
    private static function isOperator(string $class): bool
    {
        return \is_subclass_of($class, Proposition::class) || \is_subclass_of($class, VariableOperand::class);
    }

    /**
     * @return class-string<Proposition|VariableOperand>
     *
     * @throws UnknownOperatorException if the class is not a Proposition or VariableOperand
     */
    private static function operatorClass(string $class, string $name): string
    {
        if (!self::isOperator($class)) {
            throw new UnknownOperatorException(\sprintf('Cannot register "%s": %s is not a Proposition or VariableOperand.', $name, $class));
        }

        return $class;
    }
}
