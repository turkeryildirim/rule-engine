<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\Multiplication;
use Ruler\Variable;

class MultiplicationTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Multiplication($varA, $varB);
        $op->prepareValue($context);
    }

    #[DataProvider('multiplyData')]
    public function testMultiply(int|float $a, int|float $b, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Multiplication($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|float[]>
     */
    public static function multiplyData(): array
    {
        return [
            [6, 2, 12],
            [7, 3, 21],
            [2.5, 1.5, 3.75],
        ];
    }
}
