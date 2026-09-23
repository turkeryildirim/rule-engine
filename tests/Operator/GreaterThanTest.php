<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\GreaterThan;
use Ruler\Variable;

class GreaterThanTest extends TestCase
{
    public function testConstructorAndEvaluation(): void
    {
        $varA = new Variable('a', 1);
        $varB = new Variable('b', 2);
        $context = new Context();

        $op = new GreaterThan($varA, $varB);
        self::assertFalse($op->evaluate($context));

        $context['a'] = 2;
        self::assertFalse($op->evaluate($context));

        $context['a'] = 3;
        $context['b'] = (static fn (): int => 0);
        self::assertTrue($op->evaluate($context));
    }
}
