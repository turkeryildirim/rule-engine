<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Rule;
use D6N\RuleEngine\RuleSet;
use D6N\RuleEngine\Test\Fixtures\CallCounter;
use D6N\RuleEngine\Test\Fixtures\FalseProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
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

    public function testAddingTheSameRuleTwiceExecutesItOnce(): void
    {
        $action = new CallCounter();
        $rule = new Rule(new TrueProposition(), $action);

        $ruleset = new RuleSet([$rule, $rule]);
        $ruleset->addRule($rule);
        $ruleset->executeRules(new Context());

        self::assertSame(1, $action->calls);
    }

    public function testSkipsRulesWhoseConditionFails(): void
    {
        $action = new CallCounter();

        new RuleSet([new Rule(new FalseProposition(), $action)])->executeRules(new Context());

        self::assertSame(0, $action->calls);
    }
}
