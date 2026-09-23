<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\ContainsSubset;
use D6N\RuleEngine\Operator\DoesNotContainSubset;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContainsSubsetTest extends TestCase
{
    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     */
    #[DataProvider('containsData')]
    public function testContains(?array $a, int|string|array|null $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new ContainsSubset($varA, $varB);
        self::assertEquals($op->evaluate($context), $result);
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     */
    #[DataProvider('containsData')]
    public function testDoesNotContain(?array $a, int|string|array|null $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new DoesNotContainSubset($varA, $varB);
        self::assertNotEquals($op->evaluate($context), $result);
    }

    /**
     * @return array<int, bool[]|int[]|int[][]|string[][]|string[]|null[]|never[][]>
     */
    public static function containsData(): array
    {
        return [
            [[1], [1], true],
            [[1], 1, true],
            [[1, 2, 3], [1, 2], true],
            [[1, 2, 3], [2, 4], false],
            [['foo', 'bar', 'baz'], ['pow'], false],
            [['foo', 'bar', 'baz'], ['bar'], true],
            [['foo', 'bar', 'baz'], ['bar', 'baz'], true],
            [null, 'bar', false],
            [null, ['bar'], false],
            [null, ['bar', 'baz'], false],
            [null, null, true],
            [[], [], true],
            [[1, 2, 3], [2], true],
        ];
    }
}
