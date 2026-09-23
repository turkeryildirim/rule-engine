<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\SymmetricDifference;
use Ruler\Variable;

class SymmetricDifferenceTest extends TestCase
{
    public function testInvalidData(): void
    {
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new SymmetricDifference($varA, $varB);
        self::assertEquals(
            ['string', 'blah'],
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $result
     */
    #[DataProvider('symmetricDifferenceData')]
    public function testSymmetricDifference(int|string|array $a, int|string|array $b, array $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new SymmetricDifference($varA, $varB);
        self::assertEquals(
            $result,
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @return array<int, int[]|int[][]|string[]|string[][]|string[][]|never[][]>
     */
    public static function symmetricDifferenceData(): array
    {
        return [
            [6, 2, [6, 2]],
            [
                ['a', 'b', 'c'],
                'a',
                ['b', 'c'],
            ],
            [
                'a',
                ['a', 'b', 'c'],
                ['b', 'c'],
            ],
            [
                'a',
                ['b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                [],
                ['a', 'b', 'c'],
            ],
            [
                [],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['a'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['c', 'd'],
            ],
        ];
    }
}
