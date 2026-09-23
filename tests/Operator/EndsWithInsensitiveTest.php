<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\EndsWithInsensitive;
use Ruler\Variable;

class EndsWithInsensitiveTest extends TestCase
{
    #[DataProvider('endsWithData')]
    public function testEndsWithInsensitive(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new EndsWithInsensitive($varA, $varB);
        self::assertEquals($op->evaluate($context), $result);
    }

    /**
     * @return array<int, string[]|bool[]>
     */
    public static function endsWithData(): array
    {
        return [
            ['supercalifragilistic', 'supercalifragilistic', true],
            ['supercalifragilistic', 'stic', true],
            ['supercalifragilistic', 'STIC', true],
            ['supercalifragilistic', 'super', false],
            ['supercalifragilistic', '', false],
        ];
    }
}
