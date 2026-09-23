<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Division;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DivisionTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Division($varA, $varB);
        $op->prepareValue($context);
    }

    public function testDivideByZero(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Division by zero');
        $varA = new Variable('a', \random_int(1, 100));
        $varB = new Variable('b', 0);
        $context = new Context();

        $op = new Division($varA, $varB);
        $op->prepareValue($context);
    }

    #[DataProvider('divisionData')]
    public function testDivision(int|float $a, int|float $b, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Division($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|float[]>
     */
    public static function divisionData(): array
    {
        return [
            [6, 2, 3],
            [7.5, 2.5, 3.0],
        ];
    }
}
