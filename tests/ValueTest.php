<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValueTest extends TestCase
{
    public function testConstructor(): void
    {
        $valueString = 'technologic';
        $value = new Value($valueString);
        self::assertEquals($valueString, $value->getValue());
    }

    #[DataProvider('getRelativeValues')]
    public function testGreaterThanEqualToAndLessThan(int|string|\DateTime $a, int|string|\DateTime $b, bool $gt, bool $eq, bool $lt): void
    {
        $valA = new Value($a);
        $valB = new Value($b);

        self::assertEquals($gt, $valA->greaterThan($valB));
        self::assertEquals($lt, $valA->lessThan($valB));
        self::assertEquals($eq, $valA->equalTo($valB));
    }

    /**
     * @return array<int, bool[]|int[]|string[]|\DateTime[]>
     */
    public static function getRelativeValues(): array
    {
        return [
            [1, 2,     false, false, true],
            [2, 1,     true, false, false],
            [1, 1,     false, true, false],
            ['a', 'b', false, false, true],
            [
                new \DateTime('-5 days'),
                new \DateTime('+5 days'),
                false, false, true,
            ],
        ];
    }

    #[DataProvider('prefixAndSuffixCases')]
    public function testStartsWithAndEndsWithHandleEdgeCases(mixed $haystack, mixed $needle, bool $startsWith, bool $endsWith): void
    {
        $value = new Value($haystack);

        self::assertSame($startsWith, $value->startsWith(new Value($needle)));
        self::assertSame($endsWith, $value->endsWith(new Value($needle)));
    }

    /**
     * @return iterable<string, array{mixed, mixed, bool, bool}>
     */
    public static function prefixAndSuffixCases(): iterable
    {
        yield 'zero is a real prefix' => ['0abc0', '0', true, true];
        yield 'integers are strings' => [12345, 12, true, false];
        yield 'empty needle' => ['abc', '', false, false];
        yield 'needle longer' => ['ab', 'abc', false, false];
        yield 'null haystack' => [null, 'a', false, false];
        yield 'null needle' => ['abc', null, false, false];
    }

    public function testCaseInsensitivePrefixAndSuffix(): void
    {
        $value = new Value('Hello World');

        self::assertTrue($value->startsWith(new Value('hello'), true));
        self::assertTrue($value->endsWith(new Value('WORLD'), true));
        self::assertFalse($value->endsWith(new Value('WORLD')));
    }

    public function testStringContainsTreatsNullAsContainingNothing(): void
    {
        self::assertFalse(new Value(null)->stringContains(new Value('a')));
        self::assertFalse(new Value('abc')->stringContainsInsensitive(new Value(null)));
    }

    public function testStringOperationsRejectNonStrings(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('String operations: values must be strings');

        new Value(['a'])->stringContains(new Value('a'));
    }

    #[DataProvider('zeroDivisors')]
    public function testDivisionByAnyZeroThrows(int|float|string $zero): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Division by zero');

        new Value(1)->divide(new Value($zero));
    }

    #[DataProvider('zeroDivisors')]
    public function testModuloByAnyZeroThrows(int|float|string $zero): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Division by zero');

        new Value(1)->modulo(new Value($zero));
    }

    /**
     * @return iterable<string, array{int|float|string}>
     */
    public static function zeroDivisors(): iterable
    {
        yield 'int' => [0];
        yield 'float' => [0.0];
        yield 'negative zero' => [-0.0];
        yield 'string' => ['0'];
        yield 'float string' => ['0.0'];
    }

    public function testModuloUsesFmodForFloats(): void
    {
        self::assertSame(1.5, new Value(5.5)->modulo(new Value(2)));
        self::assertSame(0.0, new Value(5)->modulo(new Value(0.5)));
        self::assertSame(1, new Value(7)->modulo(new Value(3)));
    }

    public function testZeroToANegativePowerThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Division by zero');

        new Value(0)->exponentiate(new Value(-1));
    }

    public function testArithmeticAcceptsNumericStrings(): void
    {
        self::assertSame(2, new Value('1.5')->ceil());
        self::assertSame(1, new Value('1.5')->floor());
        self::assertSame(4.5, new Value('3')->add(new Value('1.5')));
    }

    public function testCeilKeepsFloatsThatDoNotFitInAnInt(): void
    {
        self::assertSame(1.0e30, new Value(1.0e30)->ceil());
    }

    public function testStringRepresentation(): void
    {
        $object = new \stdClass();

        self::assertSame(\spl_object_hash($object), (string) new Value($object));
        self::assertSame(\serialize([1, 'a']), (string) new Value([1, 'a']));
    }
}
