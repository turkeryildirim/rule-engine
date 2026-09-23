<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\StartsWith;
use Ruler\Variable;

class StartsWithTest extends TestCase
{
    #[DataProvider('startsWithData')]
    public function testStartsWith(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new StartsWith($varA, $varB);
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
            ['supercalifragilistic', 'SUPER', false],
            ['supercalifragilistic', 'stic', false],
            ['supercalifragilistic', '', false],
        ];
    }
}
