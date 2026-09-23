<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Exception\OperandCountException;
use D6N\RuleEngine\Operator\AtLeast;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Test\Fixtures\FalseProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CountingAndLogicalOperatorsTest extends TestCase
{
    #[DataProvider('pairs')]
    public function testTwoPropositionTruthTables(bool $p, bool $q): void
    {
        $rb = new RuleBuilder();
        $context = new Context();

        self::assertSame(!$p || $q, $rb->logicalImplies(self::prop($p), self::prop($q))->evaluate($context));
        self::assertSame(!($p && $q), $rb->logicalNand(self::prop($p), self::prop($q))->evaluate($context));
        self::assertSame(!($p || $q), $rb->logicalNor(self::prop($p), self::prop($q))->evaluate($context));
    }

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function pairs(): iterable
    {
        yield 'true, true' => [true, true];
        yield 'true, false' => [true, false];
        yield 'false, true' => [false, true];
        yield 'false, false' => [false, false];
    }

    #[DataProvider('counts')]
    public function testCountingOperators(int $count, bool $atLeast, bool $atMost, bool $exactly): void
    {
        $rb = new RuleBuilder();
        $props = [self::prop(true), self::prop(false), self::prop(true)]; // two hold
        $context = new Context();

        self::assertSame($atLeast, $rb->atLeast($count, ...$props)->evaluate($context));
        self::assertSame($atMost, $rb->atMost($count, ...$props)->evaluate($context));
        self::assertSame($exactly, $rb->exactly($count, ...$props)->evaluate($context));
        self::assertSame($count, $rb->atLeast($count, ...$props)->getCount());
    }

    /**
     * @return iterable<string, array{int, bool, bool, bool}>
     */
    public static function counts(): iterable
    {
        yield 'zero' => [0, true, false, false];
        yield 'one' => [1, true, false, false];
        yield 'two' => [2, true, true, true];
        yield 'three' => [3, false, true, false];
    }

    public function testCountMustNotBeNegative(): void
    {
        $this->expectException(InvalidOperandException::class);
        $this->expectExceptionMessage('count must not be negative, -1 given');

        new AtLeast(-1, [new TrueProposition()]);
    }

    public function testImplicationTakesExactlyTwoPropositions(): void
    {
        $this->expectException(OperandCountException::class);

        new RuleBuilder()->logicalImplies(self::prop(true), self::prop(true))->addProposition(self::prop(true));
    }

    private static function prop(bool $value): Proposition
    {
        return $value ? new TrueProposition() : new FalseProposition();
    }
}
