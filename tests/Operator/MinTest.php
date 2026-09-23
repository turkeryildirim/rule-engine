<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Min;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MinTest extends TestCase
{
    /**
     * @param array<mixed> $datum
     */
    #[DataProvider('invalidData')]
    public function testInvalidData(string|array $datum): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('min: all values must be numeric');
        $var = new Variable('a', $datum);
        $context = new Context();

        $op = new Min($var);
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
    #[DataProvider('minData')]
    public function testMin(int|array $a, ?int $result): void
    {
        $var = new Variable('a', $a);
        $context = new Context();

        $op = new Min($var);
        self::assertEquals(
            $result,
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @return array<int, never[][]|null[]|int[]|int[][]>
     */
    public static function minData(): array
    {
        return [
            [5, 5],
            [[], null],
            [[5], 5],
            [[-2, -5, -242], -242],
            [[2, 5, 242], 2],
        ];
    }
}
