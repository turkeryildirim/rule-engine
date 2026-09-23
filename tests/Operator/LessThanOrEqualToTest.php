<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\GreaterThanOrEqualTo;
use Ruler\Variable;

class LessThanOrEqualToTest extends TestCase
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

        $context['a'] = 2;
        self::assertFalse($op->evaluate($context));
    }
}
