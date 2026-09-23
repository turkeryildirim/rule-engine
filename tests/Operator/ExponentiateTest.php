<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\Exponentiate;
use Ruler\Variable;

class ExponentiateTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Exponentiate($varA, $varB);
        $op->prepareValue($context);
    }

    #[DataProvider('exponentiateData')]
    public function testExponentiate(int $a, int $b, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Exponentiate($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|int[]|float[]>
     */
    public static function exponentiateData(): array
    {
        return [
            [6, 2, 36],
            [10, -1, 0.1],
        ];
    }
}
