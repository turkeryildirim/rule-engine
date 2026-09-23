<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\LogicalOr;
use Ruler\Test\Fixtures\FalseProposition;
use Ruler\Test\Fixtures\TrueProposition;

class LogicalOrTest extends TestCase
{
    public function testConstructor(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalOr([$true, $false]);
        self::assertTrue($op->evaluate($context));
    }

    public function testAddPropositionAndEvaluate(): void
    {
        $true = new TrueProposition();
        $false = new FalseProposition();
        $context = new Context();

        $op = new LogicalOr();

        $op->addProposition($false);
        self::assertFalse($op->evaluate($context));

        $op->addProposition($false);
        self::assertFalse($op->evaluate($context));

        $op->addOperand($true);
        self::assertTrue($op->evaluate($context));
    }

    public function testExecutingALogicalOrWithoutPropositionsThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalOr();
        $op->evaluate(new Context());
    }
}
