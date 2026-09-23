<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\SetContains;
use D6N\RuleEngine\Operator\SetDoesNotContain;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SetContainsTest extends TestCase
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

        $op = new SetContains($varA, $varB);
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

        $op = new SetDoesNotContain($varA, $varB);
        self::assertNotEquals($op->evaluate($context), $result);
    }

    /**
     * @return array<int, string[]|bool[]|string[][]|null[]|int[]|int[][]|int[]|string[]|string[][][]>
     */
    public static function containsData(): array
    {
        return [
            [[1], 1, true],
            [[1, 2, 3], 1, true],
            [[1, 2, 3], 4, false],
            [['foo', 'bar', 'baz'], 'pow', false],
            [['foo', 'bar', 'baz'], 'bar', true],
            [null, 'bar', false],
            [null, null, false],
            [[1, 2, 3], [2], false],
            [[1, 2, ['foo']], ['foo'], true],
            [[1], [1], false],
        ];
    }
}
