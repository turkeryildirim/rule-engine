<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\ArithmeticException;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Test\Fixtures\FrozenClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Comparison, string, type, numeric and date operators, exercised through the fluent DSL.
 */
class ValueOperatorsTest extends TestCase
{
    /**
     * @param \Closure(RuleBuilder): \D6N\RuleEngine\Proposition $build
     */
    #[DataProvider('propositions')]
    public function testPropositions(\Closure $build, mixed $value, bool $expected): void
    {
        $proposition = $build(new RuleBuilder());

        self::assertSame($expected, $proposition->evaluate(new Context(['v' => $value], new FrozenClock())));
    }

    /**
     * @return iterable<string, array{\Closure(RuleBuilder): \D6N\RuleEngine\Proposition, mixed, bool}>
     */
    public static function propositions(): iterable
    {
        // Comparison
        yield 'in: member' => [static fn (RuleBuilder $rb) => $rb['v']->in(['TR', 'DE']), 'TR', true];
        yield 'in: type-sensitive' => [static fn (RuleBuilder $rb) => $rb['v']->in([1, 2]), '1', false];
        yield 'notIn: member' => [static fn (RuleBuilder $rb) => $rb['v']->notIn(['TR']), 'TR', false];
        yield 'notIn: non-member' => [static fn (RuleBuilder $rb) => $rb['v']->notIn(['TR']), 'US', true];
        yield 'between: inside' => [static fn (RuleBuilder $rb) => $rb['v']->between(1, 10), 5, true];
        yield 'between: inclusive min' => [static fn (RuleBuilder $rb) => $rb['v']->between(1, 10), 1, true];
        yield 'between: inclusive max' => [static fn (RuleBuilder $rb) => $rb['v']->between(1, 10), 10, true];
        yield 'between: below' => [static fn (RuleBuilder $rb) => $rb['v']->between(1, 10), 0, false];
        yield 'between: above' => [static fn (RuleBuilder $rb) => $rb['v']->between(1, 10), 11, false];

        // Strings
        yield 'matches' => [static fn (RuleBuilder $rb) => $rb['v']->matches('/^[A-Z]{2}\d+$/'), 'TR42', true];
        yield 'matches: no match' => [static fn (RuleBuilder $rb) => $rb['v']->matches('/^\d+$/'), 'TR42', false];
        yield 'matches: unicode' => [static fn (RuleBuilder $rb) => $rb['v']->matches('/^\p{Lu}/u'), 'Çağrı', true];
        yield 'matches: null' => [static fn (RuleBuilder $rb) => $rb['v']->matches('/.*/'), null, false];
        yield 'containsAny: one' => [static fn (RuleBuilder $rb) => $rb['v']->containsAny(['x', 'ell']), 'hello', true];
        yield 'containsAny: none' => [static fn (RuleBuilder $rb) => $rb['v']->containsAny(['x', 'y']), 'hello', false];
        yield 'containsAny: empty' => [static fn (RuleBuilder $rb) => $rb['v']->containsAny([]), 'hello', false];
        yield 'containsAll: all' => [static fn (RuleBuilder $rb) => $rb['v']->containsAll(['he', 'lo']), 'hello', true];
        yield 'containsAll: missing' => [static fn (RuleBuilder $rb) => $rb['v']->containsAll(['he', 'x']), 'hello', false];
        yield 'containsAll: empty' => [static fn (RuleBuilder $rb) => $rb['v']->containsAll([]), 'hello', true];
        yield 'startsWithAny' => [static fn (RuleBuilder $rb) => $rb['v']->startsWithAny(['x', 'he']), 'hello', true];
        yield 'startsWithAny: none' => [static fn (RuleBuilder $rb) => $rb['v']->startsWithAny(['lo']), 'hello', false];
        yield 'endsWithAny' => [static fn (RuleBuilder $rb) => $rb['v']->endsWithAny(['x', 'lo']), 'hello', true];
        yield 'endsWithAny: scalar' => [static fn (RuleBuilder $rb) => $rb['v']->endsWithAny('lo'), 'hello', true];

        // Case-insensitive, Unicode and Turkish dotted/dotless i
        yield 'insensitive: İstanbul' => [static fn (RuleBuilder $rb) => $rb['v']->stringContainsInsensitive('istanbul'), 'İSTANBUL', true];
        yield 'insensitive: ÇAĞRI' => [static fn (RuleBuilder $rb) => $rb['v']->startsWithInsensitive('çağ'), 'ÇAĞRI', true];
        yield 'insensitive: ı/I' => [static fn (RuleBuilder $rb) => $rb['v']->endsWithInsensitive('ı'), 'ÇAĞRI', true];
        yield 'insensitive: ß' => [static fn (RuleBuilder $rb) => $rb['v']->stringContainsInsensitive('STRASSE'), 'Straße', true];
        yield 'insensitive: not found' => [static fn (RuleBuilder $rb) => $rb['v']->stringDoesNotContainInsensitive('Ö'), 'öğrenci', false];

        // Types
        yield 'isNull: null' => [static fn (RuleBuilder $rb) => $rb['v']->isNull(), null, true];
        yield 'isNull: false' => [static fn (RuleBuilder $rb) => $rb['v']->isNull(), false, false];
        yield 'isEmpty: null' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), null, true];
        yield 'isEmpty: empty string' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), '', true];
        yield 'isEmpty: empty array' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), [], true];
        yield 'isEmpty: empty Countable' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), new \ArrayObject(), true];
        yield 'isEmpty: filled Countable' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), new \ArrayObject([1]), false];
        yield 'isEmpty: zero' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), 0, false];
        yield 'isEmpty: "0"' => [static fn (RuleBuilder $rb) => $rb['v']->isEmpty(), '0', false];
        yield 'isString' => [static fn (RuleBuilder $rb) => $rb['v']->isString(), '1', true];
        yield 'isString: int' => [static fn (RuleBuilder $rb) => $rb['v']->isString(), 1, false];
        yield 'isNumeric: string' => [static fn (RuleBuilder $rb) => $rb['v']->isNumeric(), '1.5', true];
        yield 'isNumeric: word' => [static fn (RuleBuilder $rb) => $rb['v']->isNumeric(), 'one', false];
        yield 'isArray' => [static fn (RuleBuilder $rb) => $rb['v']->isArray(), [], true];
        yield 'isArray: string' => [static fn (RuleBuilder $rb) => $rb['v']->isArray(), 'a', false];
        yield 'isBool' => [static fn (RuleBuilder $rb) => $rb['v']->isBool(), false, true];
        yield 'isBool: int' => [static fn (RuleBuilder $rb) => $rb['v']->isBool(), 0, false];

        // Dates (now = 2026-09-23 12:00 UTC)
        yield 'before' => [static fn (RuleBuilder $rb) => $rb['v']->before('2026-01-01'), '2025-12-31', true];
        yield 'before: same instant' => [static fn (RuleBuilder $rb) => $rb['v']->before('2026-01-01'), '2026-01-01', false];
        yield 'after: DateTime' => [static fn (RuleBuilder $rb) => $rb['v']->after(new \DateTime('2026-01-01')), '2026-01-02', true];
        yield 'after: timestamp' => [static fn (RuleBuilder $rb) => $rb['v']->after('2026-01-01 UTC'), 1_767_225_600, false];
        yield 'betweenDates: inside' => [static fn (RuleBuilder $rb) => $rb['v']->betweenDates('2026-01-01', '2026-12-31'), '2026-06-15', true];
        yield 'betweenDates: inclusive' => [static fn (RuleBuilder $rb) => $rb['v']->betweenDates('2026-01-01', '2026-12-31'), '2026-12-31', true];
        yield 'betweenDates: outside' => [static fn (RuleBuilder $rb) => $rb['v']->betweenDates('2026-01-01', '2026-12-31'), '2027-01-01', false];
        yield 'withinLast: inside' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast('7 days'), '2026-09-20 UTC', true];
        yield 'withinLast: too old' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast('7 days'), '2026-09-01 UTC', false];
        yield 'withinLast: future' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast('7 days'), '2026-09-24 UTC', false];
        yield 'withinLast: DateInterval' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast(new \DateInterval('PT1H')), '2026-09-23 11:30 UTC', true];
        yield 'olderThan: 18 years' => [static fn (RuleBuilder $rb) => $rb['v']->olderThan('18 years'), '2008-09-23 UTC', true];
        yield 'olderThan: one day short' => [static fn (RuleBuilder $rb) => $rb['v']->olderThan('18 years'), '2008-09-24 UTC', false];
    }

    /**
     * @param \Closure(RuleBuilder): RuleBuilder\Variable $build
     */
    #[DataProvider('values')]
    public function testValues(\Closure $build, mixed $value, mixed $expected): void
    {
        $variable = $build(new RuleBuilder());

        self::assertSame($expected, $variable->prepareValue(new Context(['v' => $value]))->getValue());
    }

    /**
     * @return iterable<string, array{\Closure(RuleBuilder): RuleBuilder\Variable, mixed, mixed}>
     */
    public static function values(): iterable
    {
        yield 'length: UTF-8 string' => [static fn (RuleBuilder $rb) => $rb['v']->length(), 'çağrı', 5];
        yield 'length: array' => [static fn (RuleBuilder $rb) => $rb['v']->length(), [1, 1, 2], 3];
        yield 'length: Countable' => [static fn (RuleBuilder $rb) => $rb['v']->length(), new \ArrayObject([1, 2]), 2];
        yield 'length: int' => [static fn (RuleBuilder $rb) => $rb['v']->length(), 12345, 5];
        yield 'length: null' => [static fn (RuleBuilder $rb) => $rb['v']->length(), null, 0];

        yield 'abs: int' => [static fn (RuleBuilder $rb) => $rb['v']->abs(), -3, 3];
        yield 'abs: numeric string' => [static fn (RuleBuilder $rb) => $rb['v']->abs(), '-1.5', 1.5];
        yield 'round: half up' => [static fn (RuleBuilder $rb) => $rb['v']->round(0), 2.5, 3];
        yield 'round: negative half' => [static fn (RuleBuilder $rb) => $rb['v']->round(0), -2.5, -3];
        yield 'round: precision' => [static fn (RuleBuilder $rb) => $rb['v']->round(2), 1.005, 1.01];
        yield 'round: tens' => [static fn (RuleBuilder $rb) => $rb['v']->round(-1), 1234, 1230];
        yield 'sum' => [static fn (RuleBuilder $rb) => $rb['v']->sum(), [5, 5, '2.5'], 12.5];
        yield 'sum: empty' => [static fn (RuleBuilder $rb) => $rb['v']->sum(), [], 0];
        yield 'avg' => [static fn (RuleBuilder $rb) => $rb['v']->avg(), [1, 2, 3, 4], 2.5];
        yield 'avg: whole' => [static fn (RuleBuilder $rb) => $rb['v']->avg(), [2, 4], 3];
        yield 'avg: empty' => [static fn (RuleBuilder $rb) => $rb['v']->avg(), [], null];
        yield 'count: with duplicates' => [static fn (RuleBuilder $rb) => $rb['v']->count(), [1, 1, 2], 3];
        yield 'count: Countable' => [static fn (RuleBuilder $rb) => $rb['v']->count(), new \ArrayObject([1]), 1];
        yield 'count: scalar' => [static fn (RuleBuilder $rb) => $rb['v']->count(), 'x', 1];
        yield 'count: null' => [static fn (RuleBuilder $rb) => $rb['v']->count(), null, 0];
        yield 'chained' => [static fn (RuleBuilder $rb) => $rb['v']->sum()->multiply(2)->round(0), [1.25, 1.5], 6];
    }

    /**
     * @param \Closure(RuleBuilder): (\D6N\RuleEngine\Proposition|\D6N\RuleEngine\VariableOperand) $build
     * @param class-string<\Throwable>                                                             $exception
     */
    #[DataProvider('failures')]
    public function testRejectsUnusableValues(\Closure $build, mixed $value, string $exception, string $message): void
    {
        $operator = $build(new RuleBuilder());
        $context = new Context(['v' => $value], new FrozenClock());

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $operator instanceof \D6N\RuleEngine\Proposition ? $operator->evaluate($context) : $operator->prepareValue($context);
    }

    /**
     * @return iterable<string, array{\Closure(RuleBuilder): (\D6N\RuleEngine\Proposition|\D6N\RuleEngine\VariableOperand), mixed, class-string<\Throwable>, string}>
     */
    public static function failures(): iterable
    {
        yield 'invalid regex' => [static fn (RuleBuilder $rb) => $rb['v']->matches('/('), 'a', InvalidOperandException::class, 'Invalid regular expression'];
        yield 'length of an object' => [static fn (RuleBuilder $rb) => $rb['v']->length(), new \stdClass(), InvalidOperandException::class, 'values must be strings'];
        yield 'sum of words' => [static fn (RuleBuilder $rb) => $rb['v']->sum(), ['a'], ArithmeticException::class, 'must be numeric'];
        yield 'round to a fraction' => [static fn (RuleBuilder $rb) => $rb['v']->round(1.5), 1, InvalidOperandException::class, 'precision must be an integer'];
        yield 'date from a bad string' => [static fn (RuleBuilder $rb) => $rb['v']->before('2026-01-01'), 'not a date', InvalidOperandException::class, 'Failed to parse time string'];
        yield 'date from an array' => [static fn (RuleBuilder $rb) => $rb['v']->before('2026-01-01'), [], InvalidOperandException::class, 'expected a date, array given'];
        yield 'interval from a number' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast(7), '2026-01-01', InvalidOperandException::class, 'expected an interval, int given'];
        yield 'interval from nonsense' => [static fn (RuleBuilder $rb) => $rb['v']->withinLast('nonsense'), '2026-01-01', InvalidOperandException::class, 'Unknown or bad format'];
    }

    public function testContextUsesTheSystemClockByDefault(): void
    {
        $before = new \DateTimeImmutable();
        $now = new Context()->now();

        self::assertGreaterThanOrEqual($before, $now);
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $now);
    }

    public function testRegexRuntimeErrorsAreReported(): void
    {
        $limit = \ini_get('pcre.backtrack_limit');
        $jit = \ini_get('pcre.jit');
        \ini_set('pcre.backtrack_limit', '10');
        \ini_set('pcre.jit', '0');

        try {
            $this->expectException(InvalidOperandException::class);
            $this->expectExceptionMessage('Regular expression failed: Backtrack limit exhausted');

            new RuleBuilder()['v']->matches('/(?:\D+|<\d+>)*[!?]/')->evaluate(new Context(['v' => 'foobar foobar foobar']));
        } finally {
            \ini_set('pcre.backtrack_limit', (string) $limit);
            \ini_set('pcre.jit', (string) $jit);
        }
    }
}
