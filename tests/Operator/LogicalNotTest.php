<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\LogicalNot;
use Ruler\Test\Fixtures\FalseProposition;
use Ruler\Test\Fixtures\TrueProposition;

class LogicalNotTest extends TestCase
{
    public function testConstructor(): void
    {
        $op = new LogicalNot([new FalseProposition()]);
        self::assertTrue($op->evaluate(new Context()));
    }

    public function testAddPropositionAndEvaluate(): void
    {
        $op = new LogicalNot();

        $op->addProposition(new TrueProposition());
        self::assertFalse($op->evaluate(new Context()));
    }

    public function testExecutingALogicalNotWithoutPropositionsThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalNot();
        $op->evaluate(new Context());
    }

    public function testInstantiatingALogicalNotWithTooManyArgumentsThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalNot([new TrueProposition(), new FalseProposition()]);
    }

    public function testAddingASecondPropositionToLogicalNotThrowsAnException(): void
    {
        $this->expectException(\LogicException::class);
        $op = new LogicalNot();
        $op->addProposition(new TrueProposition());
        $op->addProposition(new TrueProposition());
    }
}
