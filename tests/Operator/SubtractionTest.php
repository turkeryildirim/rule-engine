<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Subtraction;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SubtractionTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Subtraction($varA, $varB);
        $op->prepareValue($context);
    }

    #[DataProvider('subtractData')]
    public function testSubtract(int|float $a, int|float $b, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Subtraction($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|float[]>
     */
    public static function subtractData(): array
    {
        return [
            [6, 2, 4],
            [7, -3, 10],
            [2.5, 1.4, 1.1],
        ];
    }
}
