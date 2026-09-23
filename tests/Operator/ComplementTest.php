<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Complement;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ComplementTest extends TestCase
{
    public function testInvalidData(): void
    {
        $varA = new Variable('a', 'string');
        $varB = new Variable('b', 'blah');
        $context = new Context();

        $op = new Complement($varA, $varB);
        self::assertEquals(
            ['string'],
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $result
     */
    #[DataProvider('complementData')]
    public function testComplement(int|string|array $a, int|string|array $b, array $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new Complement($varA, $varB);
        self::assertEquals(
            $result,
            $op->prepareValue($context)->getValue()
        );
    }

    /**
     * @return array<int, int[]|int[][]|string[]|string[][]|string[][]>
     */
    public static function complementData(): array
    {
        return [
            [6, 2, [6]],
            [
                ['a', 'b', 'c'],
                'a',
                ['b', 'c'],
            ],
            [
                'a',
                ['a', 'b', 'c'],
                [],
            ],
            [
                'a',
                ['b', 'c'],
                ['a'],
            ],
            [
                ['a', 'b', 'c'],
                [],
                ['a', 'b', 'c'],
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
                ['a', 'b', 'c'],
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
                ['c'],
            ],
        ];
    }
}
