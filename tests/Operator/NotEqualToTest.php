<?php

declare(strict_types=1);

namespace Ruler\Test\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Operator\NotEqualTo;
use Ruler\Variable;

class NotEqualToTest extends TestCase
{
    public function testResolvesVariablesFromContext(): void
    {
        $op = new NotEqualTo(new Variable('a', 1), new Variable('b', 2));
        $context = new Context();

        self::assertTrue($op->evaluate($context));

        $context['a'] = 2;
        self::assertFalse($op->evaluate($context));

        $context['a'] = 3;
        $context['b'] = static fn (): int => 3;
        self::assertFalse($op->evaluate($context));
    }

    #[DataProvider('comparisons')]
    public function testComparesUsingStrictEquality(mixed $left, mixed $right, bool $strict, bool $loose): void
    {
        $op = new NotEqualTo(new Variable('left', $left), new Variable('right', $right));

        self::assertSame(!$strict, $op->evaluate(new Context()));
    }

    /**
     * @return iterable<string, array{mixed, mixed, bool, bool}> left, right, strictly equal, loosely equal
     */
    public static function comparisons(): iterable
    {
        $object = new \stdClass();
        $twin = new \stdClass();

        yield 'identical ints' => [3, 3, true, true];
        yield 'different ints' => [1, 2, false, false];
        yield 'int and numeric string' => [3, '3', false, true];
        yield 'int and float' => [1, 1.0, false, true];
        yield 'int and true' => [1, true, false, true];
        yield 'null and false' => [null, false, false, true];
        yield 'zero and non-numeric str' => [0, 'a', false, false];
        yield 'same object' => [$object, $object, true, true];
        yield 'equal objects' => [$object, $twin, false, true];
    }
}
