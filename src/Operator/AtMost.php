<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

/**
 * True when at most $count propositions hold.
 */
class AtMost extends CountingOperator
{
    #[\Override]
    protected function accepts(int $holding, int $count): bool
    {
        return $holding <= $count;
    }
}
