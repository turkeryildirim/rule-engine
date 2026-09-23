<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * The Proposition interface represents a propositional statement.
 */
interface Proposition
{
    /**
     * Evaluate the Proposition with the given Context.
     *
     * @param Context $context Context with which to evaluate this Proposition
     */
    public function evaluate(Context $context): bool;
}
