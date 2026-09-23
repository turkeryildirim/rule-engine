<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\MatchMode;
use D6N\RuleEngine\Rule;
use D6N\RuleEngine\RuleOrder;
use D6N\RuleEngine\RuleSet;
use D6N\RuleEngine\Test\Fixtures\CallCounter;
use D6N\RuleEngine\Test\Fixtures\FalseProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleSetTest extends TestCase
{
    public function testExecutesEveryRuleThatHasBeenAdded(): void
    {
        $context = new Context();
        $actionA = new CallCounter();
        $actionC = new CallCounter();
        $ruleA = new Rule(new TrueProposition(), $actionA);
        $ruleC = new Rule(new TrueProposition(), $actionC);

        $ruleset = new RuleSet([$ruleA]);
        $ruleset->executeRules($context);

        self::assertSame(1, $actionA->calls);
        self::assertSame(0, $actionC->calls);

        $ruleset->addRule($ruleC);
        $ruleset->executeRules($context);

        self::assertSame(2, $actionA->calls);
        self::assertSame(1, $actionC->calls);
    }

    public function testAddingTheSameRuleAgainKeepsItsPositionAndUpdatesItsPriority(): void
    {
        $action = new CallCounter();
        $first = new Rule(new TrueProposition(), $action, 'first');
        $second = new Rule(new TrueProposition(), null, 'second');

        $ruleset = new RuleSet([$first, $second, $first]);
        $ruleset->addRule($first, 5);
        $ruleset->executeRules(new Context());

        self::assertSame(1, $action->calls);
        self::assertSame(['first', 'second'], self::names($ruleset->getRules()));
        self::assertSame(5, $ruleset->getPriority($first));
        self::assertSame(0, $ruleset->getPriority($second));
        self::assertNull($ruleset->getPriority(new Rule(new TrueProposition())));
    }

    public function testPriorityOrderIsStableForEqualPriorities(): void
    {
        $ruleset = new RuleSet();
        foreach (['a' => 0, 'b' => 10, 'c' => 0, 'd' => 10, 'e' => -1] as $name => $priority) {
            $ruleset->addRule(new Rule(new TrueProposition(), null, $name), $priority);
        }

        self::assertSame(['a', 'b', 'c', 'd', 'e'], self::names($ruleset->getRules()));
        self::assertSame(['b', 'd', 'a', 'c', 'e'], self::names($ruleset->getRules(RuleOrder::Priority)));
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('modes')]
    public function testExecutesTheRulesSelectedByModeAndOrder(MatchMode $mode, RuleOrder $order, array $expected): void
    {
        [$ruleset, $counters] = self::fixture();

        $fired = $ruleset->executeRules(new Context(), $mode, $order);

        self::assertSame($expected, self::names($fired));
        foreach ($counters as $name => $counter) {
            self::assertSame(\in_array($name, $expected, true) ? 1 : 0, $counter->calls, $name);
        }
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('modes')]
    public function testEvaluatesTheSameRulesWithoutRunningActions(MatchMode $mode, RuleOrder $order, array $expected): void
    {
        [$ruleset, $counters] = self::fixture();

        self::assertSame($expected, self::names($ruleset->evaluateRules(new Context(), $mode, $order)));
        foreach ($counters as $counter) {
            self::assertSame(0, $counter->calls);
        }
    }

    /**
     * Insertion order: low(1, matches), never(9, fails), high(9, matches), mid(5, matches).
     * Priority order:  never, high, mid, low.
     *
     * @return iterable<string, array{MatchMode, RuleOrder, list<string>}>
     */
    public static function modes(): iterable
    {
        yield 'all, insertion order' => [MatchMode::All, RuleOrder::Insertion, ['low', 'high', 'mid']];
        yield 'all, priority order' => [MatchMode::All, RuleOrder::Priority, ['high', 'mid', 'low']];
        yield 'first match, insertion order' => [MatchMode::First, RuleOrder::Insertion, ['low']];
        yield 'first match, priority order' => [MatchMode::First, RuleOrder::Priority, ['high']];
        yield 'last match, insertion order' => [MatchMode::Last, RuleOrder::Insertion, ['mid']];
        yield 'last match, priority order' => [MatchMode::Last, RuleOrder::Priority, ['low']];
    }

    public function testNoMatchReturnsNothing(): void
    {
        $ruleset = new RuleSet([new Rule(new FalseProposition())]);

        self::assertSame([], $ruleset->executeRules(new Context(), MatchMode::First));
        self::assertSame([], $ruleset->evaluateRules(new Context(), MatchMode::Last));
    }

    /**
     * @return array{RuleSet, array<string, CallCounter>}
     */
    private static function fixture(): array
    {
        $ruleset = new RuleSet();
        $counters = [];
        foreach (['low' => [1, true], 'never' => [9, false], 'high' => [9, true], 'mid' => [5, true]] as $name => [$priority, $matches]) {
            $counters[$name] = new CallCounter();
            $condition = $matches ? new TrueProposition() : new FalseProposition();
            $ruleset->addRule(new Rule($condition, $counters[$name], $name), $priority);
        }

        return [$ruleset, $counters];
    }

    /**
     * @param list<Rule> $rules
     *
     * @return list<string|null>
     */
    private static function names(array $rules): array
    {
        return \array_map(static fn (Rule $rule): ?string => $rule->getName(), $rules);
    }
}
