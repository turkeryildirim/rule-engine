<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Negation;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NegationTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $context = new Context();

        $op = new Negation($varA);
        $op->prepareValue($context);
    }

    #[DataProvider('negateData')]
    public function testSubtract(int|float|string $a, int|float $result): void
    {
        $varA = new Variable('a', $a);
        $context = new Context();

        $op = new Negation($varA);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|0.0[]|string[]|int[]>
     */
    public static function negateData(): array
    {
        return [
            [1, -1],
            [0.0, 0.0],
            ['0', 0],
            [-62834, 62834],
        ];
    }
}
