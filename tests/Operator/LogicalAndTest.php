<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\LogicalAnd;
use Ruler\Test\Fixtures\FalseProposition;
use Ruler\Test\Fixtures\TrueProposition;

class LogicalAndTest extends TestCase
{
    public function testConstructor(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalAnd([$true, $false]);
        self::assertFalse($op->evaluate($context));
    }

    public function testAddPropositionAndEvaluate(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalAnd();

        $op->addProposition($true);
        self::assertTrue($op->evaluate($context));

        $op->addOperand($true);
        self::assertTrue($op->evaluate($context));

        $op->addProposition($false);
        self::assertFalse($op->evaluate($context));
    }

    public function testExecutingALogicalAndWithoutPropositionsThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalAnd();
        $op->evaluate(new Context());
    }
}
