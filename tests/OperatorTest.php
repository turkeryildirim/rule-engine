<?php

declare(strict_types=1);

namespace Ruler\Test;

use PHPUnit\Framework\TestCase;
use Ruler\Operator\EqualTo;
use Ruler\Operator\LogicalAnd;
use Ruler\Operator\LogicalNot;
use Ruler\Test\Fixtures\TrueProposition;
use Ruler\Variable;

class OperatorTest extends TestCase
{
    public function testBinaryOperatorRejectsAThirdOperandImmediately(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EqualTo takes exactly 2 operands');

        new EqualTo(new Variable(null, 1), new Variable(null, 1), new Variable(null, 1));
    }

    public function testUnaryOperatorRejectsASecondOperandImmediately(): void
    {
        $op = new LogicalNot([new TrueProposition()]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('LogicalNot takes exactly 1 operand');

        $op->addProposition(new TrueProposition());
    }

    public function testIncompleteOperandListIsReportedOnUse(): void
    {
        $op = new EqualTo(new Variable(null, 1));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('EqualTo takes exactly 2 operands, 1 given');

        $op->getOperands();
    }

    public function testMultipleOperatorNeedsAtLeastOneOperand(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('LogicalAnd takes at least 1 operand, 0 given');

        new LogicalAnd()->getOperands();
    }
}
