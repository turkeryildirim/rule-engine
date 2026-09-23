<?php

declare(strict_types=1);

namespace Ruler\Test;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Rule;
use Ruler\Test\Fixtures\CallbackProposition;
use Ruler\Test\Fixtures\CallCounter;
use Ruler\Test\Fixtures\FalseProposition;
use Ruler\Test\Fixtures\TrueProposition;

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
