<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Operator\Cardinality;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CardinalityTest extends TestCase
{
    /**
     * @param array{bool, bool, bool, bool} $accepts   acceptsAnother() for 0..3 operands
     * @param array{bool, bool, bool, bool} $satisfied isSatisfiedBy() for 0..3 operands
     */
    #[DataProvider('cases')]
    public function testEveryCase(Cardinality $cardinality, array $accepts, array $satisfied, string $description): void
    {
        foreach ([0, 1, 2, 3] as $count) {
            self::assertSame($accepts[$count], $cardinality->acceptsAnother($count), "acceptsAnother($count)");
            self::assertSame($satisfied[$count], $cardinality->isSatisfiedBy($count), "isSatisfiedBy($count)");
        }
        self::assertSame($description, $cardinality->describe());
    }

    /**
     * @return iterable<string, array{Cardinality, array{bool, bool, bool, bool}, array{bool, bool, bool, bool}, string}>
     */
    public static function cases(): iterable
    {
        yield 'unary' => [Cardinality::Unary, [true, false, false, false], [false, true, false, false], 'exactly 1 operand'];
        yield 'binary' => [Cardinality::Binary, [true, true, false, false], [false, false, true, false], 'exactly 2 operands'];
        yield 'ternary' => [Cardinality::Ternary, [true, true, true, false], [false, false, false, true], 'exactly 3 operands'];
        yield 'multiple' => [Cardinality::Multiple, [true, true, true, true], [false, true, true, true], 'at least 1 operand'];
    }
}
