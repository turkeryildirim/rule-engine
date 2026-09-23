<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Addition;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdditionTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Addition($varA, $varB);
        $op->prepareValue($context);
    }

    #[DataProvider('additionData')]
    public function testAddition(int|float $a, int|float $b, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Addition($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|float[]>
     */
    public static function additionData(): array
    {
        return [
            [1, 2, 3],
            [2.5, 3.8, 6.3],
        ];
    }
}
