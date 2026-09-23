<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

/**
 * True when at least $count propositions hold.
 */
class AtLeast extends CountingOperator
{
    #[\Override]
    protected function accepts(int $holding, int $count): bool
    {
        return $holding >= $count;
    }
}
