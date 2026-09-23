<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Rule;
use D6N\RuleEngine\Test\Fixtures\CallbackProposition;
use D6N\RuleEngine\Test\Fixtures\CallCounter;
use D6N\RuleEngine\Test\Fixtures\FalseProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    public function testEvaluatesTheConditionWithTheContext(): void
    {
        $context = new Context();
        $spy = new CallCounter();
        $condition = new CallbackProposition(static function (Context $given) use ($spy): bool {
            $spy($given);

            return true;
        });

        new Rule($condition)->evaluate($context);

        self::assertSame([[$context]], $spy->arguments);
    }

    public function testExecuteRunsTheActionOnlyWhenTheConditionHolds(): void
    {
        $context = new Context();
        $whenFalse = new CallCounter();
        $whenTrue = new CallCounter();
        $falseRule = new Rule(new FalseProposition(), $whenFalse);
        $trueRule = new Rule(new TrueProposition(), $whenTrue);

        self::assertFalse($falseRule->evaluate($context));
        self::assertTrue($trueRule->evaluate($context));

        $falseRule->execute($context);
        $trueRule->execute($context);

        self::assertSame(0, $whenFalse->calls);
        self::assertSame([[$context]], $whenTrue->arguments);
    }

    public function testNonCallableActionIsRejectedAtConstruction(): void
    {
        $this->expectException(\TypeError::class);

        new Rule(new TrueProposition(), 'this is not callable'); // @phpstan-ignore argument.type, new.resultUnused (invalid input on purpose)
    }
}
