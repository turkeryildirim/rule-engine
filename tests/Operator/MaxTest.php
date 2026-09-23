<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\Max;
use Ruler\Variable;

class MaxTest extends TestCase
{
    /**
     * @param array<mixed> $datum
     */
    #[DataProvider('invalidData')]
    public function testInvalidData(string|array $datum): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('max: all values must be numeric');
        $var = new Variable('a', $datum);
        $context = new Context();

        $op = new Max($var);
        $op->prepareValue($context);
    }

    /**
     * @return array<int, string[]|array<int, array<int|string>>>
     */
    public static function invalidData(): array
    {
        return [
            ['string'],
            [['string']],
            [[1, 2, 3, 'string']],
            [['string', 1, 2, 3]],
        ];
    }

    /**
     * @param array<mixed> $a
     */
    #[DataProvider('maxData')]
    public function testMax(int|array $a, ?int $result): void
    {
        $var = new Variable('a', $a);
        $context = new Context();

        $op = new Max($var);
        self::assertEquals(
            $result,
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @return array<int, never[][]|null[]|int[]|int[][]>
     */
    public static function maxData(): array
    {
        return [
            [5, 5],
            [[], null],
            [[5], 5],
            [[-2, -5, -242], -2],
            [[2, 5, 242], 242],
        ];
    }
}
