<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\LessThan;
use Ruler\Variable;

class LessThanTest extends TestCase
{
    public function testConstructorAndEvaluation(): void
    {
        $varA = new Variable('a', 1);
        $varB = new Variable('b', 2);
        $context = new Context();

        $op = new LessThan($varA, $varB);
        self::assertTrue($op->evaluate($context));

        $context['a'] = 2;
        self::assertFalse($op->evaluate($context));

        $context['a'] = 3;
        $context['b'] = (static fn (): int => 1);
        self::assertFalse($op->evaluate($context));
    }
}
