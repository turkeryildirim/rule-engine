<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\Floor;
use Ruler\Variable;

class FloorTest extends TestCase
{
    public function testInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arithmetic: values must be numeric');
        $varA = new Variable('a', 'string');
        $context = new Context();

        $op = new Floor($varA);
        $op->prepareValue($context);
    }

    #[DataProvider('ceilingData')]
    public function testCeiling(float|int $a, int $result): void
    {
        $varA = new Variable('a', $a);
        $context = new Context();

        $op = new Floor($varA);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, float[]|int[]>
     */
    public static function ceilingData(): array
    {
        return [
            [1.2, 1],
            [1.0, 1],
            [1, 1],
            [-0.5, -1],
            [-1.5, -2],
        ];
    }
}
