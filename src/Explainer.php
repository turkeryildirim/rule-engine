<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

use D6N\RuleEngine\Exception\OperandCountException;
use D6N\RuleEngine\Exception\UnknownOperatorException;

/**
 * Evaluates every node of a rule tree and records what each one produced.
 *
 * Unlike normal evaluation, nothing is short-circuited: all operands are
 * evaluated, and an operand is evaluated again for each operator above it.
 * Facts defined with Context::share() are resolved once; other closures run
 * every time they are read.
 */
final class Explainer
{
    private readonly OperatorRegistry $operators;

    public function __construct(?OperatorRegistry $operators = null)
    {
        $this->operators = $operators ?? new OperatorRegistry();
    }

    public function explain(Proposition|VariableOperand $node, Context $context, string $path = ''): Explanation
    {
        // An anonymous Variable wrapping an operator (from the fluent DSL) is explained as that operator
        $wrapped = $node instanceof Variable && !$node instanceof PropertyReference && null === $node->getName() ? $node->getValue() : null;
        if ($wrapped instanceof VariableOperand) {
            return $this->explain($wrapped, $context, $path);
        }

        if ($node instanceof Rule) {
            // Take the result from the condition; calling Rule::evaluate() here would recurse on failure
            $condition = $this->explain($node->getCondition(), $context, self::join($path, 'condition'));

            return new Explanation($path, 'rule', $node->getName(), $condition->result, $condition->error, [$condition]);
        }

        [$type, $name, $children] = match (true) {
            $node instanceof PropertyReference => ['property', $node->getName(), [$this->explain($node->getParent(), $context, self::join($path, 'of'))]],
            $node instanceof Variable          => null === $node->getName() ? ['value', null, []] : ['var', $node->getName(), []],
            default                            => ['operator', $this->nameOf($node), $this->explainOperands($node, $context, $path)],
        };

        try {
            $result = $node instanceof Proposition ? $node->evaluate($context) : $node->prepareValue($context)->getValue();

            return new Explanation($path, $type, $name, $result, null, $children);
        } catch (\Throwable $e) {
            return new Explanation($path, $type, $name, null, $e, $children);
        }
    }

    /**
     * @return list<Explanation> empty for non-operators and for operators with an invalid operand count
     */
    private function explainOperands(Proposition|VariableOperand $node, Context $context, string $path): array
    {
        try {
            $operands = $node instanceof Operator ? $node->getOperands() : [];
        } catch (OperandCountException) {
            return []; // evaluating the node reports the same problem
        }

        $explained = [];
        foreach ($operands as $i => $operand) {
            $explained[] = $this->explain($operand, $context, self::join($path, "operands[$i]"));
        }

        return $explained;
    }

    private function nameOf(Proposition|VariableOperand $node): string
    {
        try {
            return $this->operators->nameOf($node);
        } catch (UnknownOperatorException) {
            return $node::class;
        }
    }

    private static function join(string $path, string $segment): string
    {
        return '' === $path ? $segment : $path.'.'.$segment;
    }
}
