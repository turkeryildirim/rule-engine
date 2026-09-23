<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\StartsWithInsensitive;
use Ruler\Variable;

class StartsWithInsensitiveTest extends TestCase
{
    #[DataProvider('startsWithData')]
    public function testStartsWithInsensitive(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StartsWithInsensitive($varA, $varB);
        self::assertEquals($op->evaluate($context), $result);
    }

    /**
     * @return array<int, string[]|bool[]>
     */
    public static function startsWithData(): array
    {
        return [
            ['supercalifragilistic', 'supercalifragilistic', true],
            ['supercalifragilistic', 'super', true],
            ['supercalifragilistic', 'SUPER', true],
            ['supercalifragilistic', 'stic', false],
            ['supercalifragilistic', '', false],
        ];
    }
}
