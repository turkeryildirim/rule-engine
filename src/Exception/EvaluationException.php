<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

use D6N\RuleEngine\Explanation;

/**
 * Evaluating a Rule failed. Wraps the original error (see getPrevious()) with
 * the rule name, the failing node and a full explanation of the rule.
 *
 * Message: Rule "freeShipping" failed at condition.operands[1] (logicalAnd > divide): Division by zero
 */
class EvaluationException extends \RuntimeException implements RuleEngineException
{
    public function __construct(
        private readonly ?string $ruleName,
        private readonly Explanation $explanation,
        \Throwable $previous,
    ) {
        $trail = \array_slice($explanation->failureTrail(), 1); // without the rule itself
        $location = [] === $trail
            ? ''
            : \sprintf(' at %s (%s)', \end($trail)->path, \implode(' > ', \array_map(
                static fn (Explanation $node): string => $node->name ?? $node->type,
                $trail,
            )));

        parent::__construct(
            \sprintf('Rule %s failed%s: %s', null === $ruleName ? '(unnamed)' : '"'.$ruleName.'"', $location, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public function getRuleName(): ?string
    {
        return $this->ruleName;
    }

    /**
     * Path of the deepest failing node, in exported-JSON notation, e.g. "condition.operands[1]".
     */
    public function getFailurePath(): string
    {
        $trail = $this->explanation->failureTrail();

        return [] === $trail ? '' : \end($trail)->path;
    }

    /**
     * The whole rule tree with every node's result or error.
     */
    public function getExplanation(): Explanation
    {
        return $this->explanation;
    }
}
