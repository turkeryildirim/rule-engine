<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Ceil;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CeilTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $context = new Context();

        $op = new Ceil($varA);
        $op->prepareValue($context);
    }

    #[DataProvider('ceilingData')]
    public function testCeiling(float|int $a, int $result): void
    {
        $varA = new Variable('a', $a);
        $context = new Context();

        $op = new Ceil($varA);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, float[]|int[]>
     */
    public static function ceilingData(): array
    {
        return [
            [1.2, 2],
            [1.0, 1],
            [1, 1],
            [-0.5, 0],
            [-1.5, -1],
        ];
    }
}
