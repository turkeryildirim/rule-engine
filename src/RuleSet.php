<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

use D6N\RuleEngine\Exception\EvaluationException;

/**
 * A RuleSet.
 */
class RuleSet
{
    /** @var array<int, Rule> Rules indexed by object id, in insertion order */
    private array $rules = [];

    /** @var array<int, int> priorities indexed by Rule object id */
    private array $priorities = [];

    /**
     * RuleSet constructor.
     *
     * @param iterable<Rule> $rules Rules to add with the default priority (0)
     */
    public function __construct(iterable $rules = [])
    {
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Add a Rule to the RuleSet.
     *
     * Adding a Rule that is already in the set keeps its position and only
     * updates its priority.
     *
     * @param int $priority used by RuleOrder::Priority; higher runs first
     */
    public function addRule(Rule $rule, int $priority = 0): void
    {
        $id = \spl_object_id($rule);
        $this->rules[$id] = $rule;
        $this->priorities[$id] = $priority;
    }

    public function getPriority(Rule $rule): ?int
    {
        return $this->priorities[\spl_object_id($rule)] ?? null;
    }

    /**
     * @return list<Rule>
     */
    public function getRules(RuleOrder $order = RuleOrder::Insertion): array
    {
        $rules = \array_values($this->rules);

        if (RuleOrder::Priority === $order) {
            // usort is stable, so equal priorities keep their insertion order
            \usort($rules, fn (Rule $a, Rule $b): int => $this->priorities[\spl_object_id($b)] <=> $this->priorities[\spl_object_id($a)]);
        }

        return $rules;
    }

    /**
     * Execute the matching Rules and return them.
     *
     * With MatchMode::All every Rule is executed in order, so an action can
     * change facts that later Rules read.
     *
     * @return list<Rule> the Rules whose condition held and whose action ran
     *
     * @throws EvaluationException if a condition fails
     */
    public function executeRules(Context $context, MatchMode $mode = MatchMode::All, RuleOrder $order = RuleOrder::Insertion): array
    {
        return $this->walk($context, $mode, $order, static fn (Rule $rule): bool => $rule->execute($context));
    }

    /**
     * Return the matching Rules without running any action.
     *
     * @return list<Rule>
     *
     * @throws EvaluationException if a condition fails
     */
    public function evaluateRules(Context $context, MatchMode $mode = MatchMode::All, RuleOrder $order = RuleOrder::Insertion): array
    {
        return $this->walk($context, $mode, $order, static fn (Rule $rule): bool => $rule->evaluate($context));
    }

    /**
     * @param \Closure(Rule): bool $matches evaluates (and possibly executes) one Rule
     *
     * @return list<Rule>
     */
    private function walk(Context $context, MatchMode $mode, RuleOrder $order, \Closure $matches): array
    {
        $rules = $this->getRules($order);
        if (MatchMode::Last === $mode) {
            $rules = \array_reverse($rules);
        }

        $matched = [];
        foreach ($rules as $rule) {
            if ($matches($rule)) {
                $matched[] = $rule;
                if (MatchMode::All !== $mode) {
                    break;
                }
            }
        }

        return $matched;
    }
}
