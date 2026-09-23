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

namespace Ruler;

/**
 * Rule class.
 *
 * A Rule is a conditional Proposition with an (optional) action which is
 * executed upon successful evaluation.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 */
class Rule implements Proposition
{
    protected readonly ?\Closure $action;

    /**
     * Rule constructor.
     *
     * @param Proposition   $condition Propositional condition for this Rule
     * @param callable|null $action    Called with the Context when the Rule is executed and its condition holds
     */
    public function __construct(protected readonly Proposition $condition, ?callable $action = null)
    {
        $this->action = null === $action ? null : $action(...);
    }

    /**
     * Evaluate the Rule with the given Context.
     *
     * @param Context $context Context with which to evaluate this Rule
     */
    #[\Override]
    public function evaluate(Context $context): bool
    {
        return $this->condition->evaluate($context);
    }

    /**
     * Execute the Rule with the given Context.
     *
     * The Rule will be evaluated, and if successful, its action is called with
     * the Context as its only argument.
     *
     * @param Context $context Context with which to execute this Rule
     */
    public function execute(Context $context): void
    {
        if ($this->evaluate($context) && null !== $this->action) {
            ($this->action)($context);
        }
    }
}
