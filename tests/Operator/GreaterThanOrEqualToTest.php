<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\GreaterThanOrEqualTo;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class GreaterThanOrEqualToTest extends TestCase
{
    public function testConstructorAndEvaluation(): void
    {
        $varA = new Variable('a', 1);
        $varB = new Variable('b', 2);
        $context = new Context();

        $op = new GreaterThanOrEqualTo($varA, $varB);
        self::assertFalse($op->evaluate($context));

        $context['a'] = 2;
        self::assertTrue($op->evaluate($context));

        $context['a'] = 3;
        $context['b'] = (static fn (): int => 3);
        self::assertTrue($op->evaluate($context));

        $context['4'] = 3;
        self::assertTrue($op->evaluate($context));
    }
}
