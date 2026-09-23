<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Proposition;

/**
 * Logical operator base class.
 */
abstract class LogicalOperator extends PropositionOperator implements Proposition
{
    /**
     * @param list<Proposition> $props
     */
    public function __construct(array $props = [])
    {
        parent::__construct(...$props);
    }
}
