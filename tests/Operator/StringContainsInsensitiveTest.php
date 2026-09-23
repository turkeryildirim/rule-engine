<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\StringContainsInsensitive;
use Ruler\Operator\StringDoesNotContainInsensitive;
use Ruler\Variable;

class StringContainsInsensitiveTest extends TestCase
{
    #[DataProvider('containsData')]
    public function testContains(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StringContainsInsensitive($varA, $varB);
        self::assertEquals($op->evaluate($context), $result);
    }

    #[DataProvider('containsData')]
    public function testDoesNotContain(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StringDoesNotContainInsensitive($varA, $varB);
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
            ['timmy', 'tim', true],
            ['supercalifragilistic', 'SUPER', true],
            ['supercalifragilistic', 'frAgil', true],
            ['supercalifragilistic', 'A', true],
            ['supercalifragilistic', 'sTiC', true],
            ['timmy', 'bob', false],
            ['timmy', 'TIM', true],
            ['tim', 'TIM', true],
            ['tim', 'TiM', true],
        ];
    }
}
