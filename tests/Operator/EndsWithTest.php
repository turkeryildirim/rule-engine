<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\EndsWith;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EndsWithTest extends TestCase
{
    #[DataProvider('endsWithData')]
    public function testEndsWith(string $a, string $b, bool $result): void
    {
        $varA = new Variable('a', $a);
        $varB = new Variable('b', $b);
        $context = new Context();

        $op = new EndsWith($varA, $varB);
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
            ['supercalifragilistic', 'STIC', false],
            ['supercalifragilistic', 'super', false],
            ['supercalifragilistic', '', false],
        ];
    }
}
