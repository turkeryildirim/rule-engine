<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Intersect;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IntersectTest extends TestCase
{
    public function testInvalidData(): void
    {
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Intersect($varA, $varB);
        self::assertEquals(
            [],
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $result
     */
    #[DataProvider('intersectData')]
    public function testIntersect(int|array $a, int|string|array $b, array $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Intersect($varA, $varB);
        self::assertEquals(
            $result,
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @return array<int, int[]|never[][]|string[]|string[][]|string[][]>
     */
    public static function intersectData(): array
    {
        return [
            [6, 2, []],
            [
                ['a', 'c'],
                'a',
                ['a'],
            ],
            [
                ['a', 'b', 'c'],
                [],
                [],
            ],
            [
                [],
                ['a', 'b', 'c'],
                [],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['b', 'c'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['b'],
            ],
        ];
    }
}
