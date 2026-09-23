<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\StringContains;
use D6N\RuleEngine\Operator\StringDoesNotContain;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringContainsTest extends TestCase
{
    #[DataProvider('containsData')]
    public function testContains(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StringContains($varA, $varB);
        self::assertEquals($op->evaluate($context), $result);
    }

    #[DataProvider('containsData')]
    public function testDoesNotContain(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StringDoesNotContain($varA, $varB);
        self::assertNotEquals($op->evaluate($context), $result);
    }

    /**
     * @return array<int, string[]|bool[]>
     */
    public static function containsData(): array
    {
        return [
            ['supercalifragilistic', 'super', true],
            ['supercalifragilistic', 'fragil', true],
            ['supercalifragilistic', 'a', true],
            ['supercalifragilistic', 'stic', true],
            ['timmy', 'bob', false],
            ['tim', 'TIM', false],
        ];
    }
}
