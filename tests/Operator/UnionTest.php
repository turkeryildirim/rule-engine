<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Union;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnionTest extends TestCase
{
    public function testInvalidData(): void
    {
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Union($varA, $varB);
        self::assertEquals(
            $op->prepareValue($context)->getValue(),
            [
                'string',
                'blah',
            ]
        );
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $result
     */
    #[DataProvider('unionData')]
    public function testUnion(int|string|array $a, int|array $b, array $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Union($varA, $varB);
        self::assertEquals($op->prepareValue($context)->getValue(), $result);
    }

    /**
     * @return array<int, int[]|int[][]|never[][]|string[]|string[][]|string[][]>
     */
    public static function unionData(): array
    {
        return [
            [6, 2, [6, 2]],
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
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['b', 'c', 'd'],
            ],
        ];
    }
}
