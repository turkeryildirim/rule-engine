<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\LogicalXor;
use Ruler\Test\Fixtures\FalseProposition;
use Ruler\Test\Fixtures\TrueProposition;

class LogicalXorTest extends TestCase
{
    public function testConstructor(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalXor([$true, $false]);
        self::assertTrue($op->evaluate($context));
    }

    public function testAddPropositionAndEvaluate(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalXor();

        $op->addProposition($false);
        self::assertFalse($op->evaluate($context));

        $op->addOperand($false);
        self::assertFalse($op->evaluate($context));

        $op->addProposition($true);
        self::assertTrue($op->evaluate($context));

        $op->addOperand($true);
        self::assertFalse($op->evaluate($context));
    }

    public function testExecutingALogicalXorWithoutPropositionsThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalXor();
        $op->evaluate(new Context());
    }
}
