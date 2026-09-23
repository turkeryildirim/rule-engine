<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Functional;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\RuleBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    #[DataProvider('truthTableTwo')]
    public function testDeMorgan(bool $p, bool $q): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q'));
        self::assertEquals(
            $rb->create(
                $rb->logicalNot(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalOr(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true)
                    ),
                    $rb->logicalNot(
                        $rb['q']->equalTo(true)
                    )
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableTwo')]
    public function testDeMorganTwo(bool $p, bool $q): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q'));
        self::assertEquals(
            $rb->create(
                $rb->logicalNot(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalAnd(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true)
                    ),
                    $rb->logicalNot(
                        $rb['q']->equalTo(true)
                    )
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableTwo')]
    public function testCommutation(bool $p, bool $q): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q'));
        self::assertEquals(
            $rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb['q']->equalTo(true)
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalOr(
                    $rb['q']->equalTo(true),
                    $rb['p']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableTwo')]
    public function testCommutationTwo(bool $p, bool $q): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q'));
        self::assertEquals(
            $rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb['q']->equalTo(true)
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalAnd(
                    $rb['q']->equalTo(true),
                    $rb['p']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableThree')]
    public function testAssociation(bool $p, bool $q, bool $r): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q', 'r'));
        self::assertEquals(
            $rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalOr(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalOr(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    ),
                    $rb['r']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableThree')]
    public function testAssociationTwo(bool $p, bool $q, bool $r): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q', 'r'));
        self::assertEquals(
            $rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb->logicalAnd(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalAnd(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    ),
                    $rb['r']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableThree')]
    public function testDistribution(bool $p, bool $q, bool $r): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q', 'r'));
        self::assertEquals(
            $rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb->logicalOr(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalOr(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    ),
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableThree')]
    public function testDistributionTwo(bool $p, bool $q, bool $r): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p', 'q', 'r'));
        self::assertEquals(
            $rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalAnd(
                        $rb['q']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context),
            $rb->create(
                $rb->logicalAnd(
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['q']->equalTo(true)
                    ),
                    $rb->logicalOr(
                        $rb['p']->equalTo(true),
                        $rb['r']->equalTo(true)
                    )
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableOne')]
    public function testDoubleNegation(bool $p): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p'));
        self::assertEquals(
            $rb->create(
                $rb['p']->equalTo(true)
            )->evaluate($context),
            $rb->create(
                $rb->logicalNot(
                    $rb->logicalNot(
                        $rb['p']->equalTo(true)
                    )
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableOne')]
    public function testTautology(bool $p): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p'));
        self::assertEquals(
            $rb->create(
                $rb['p']->equalTo(true)
            )->evaluate($context),
            $rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb['p']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableOne')]
    public function testTautologyTwo(bool $p): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p'));
        self::assertEquals(
            $rb->create(
                $rb['p']->equalTo(true)
            )->evaluate($context),
            $rb->create(
                $rb->logicalAnd(
                    $rb['p']->equalTo(true),
                    $rb['p']->equalTo(true)
                )
            )->evaluate($context)
        );
    }

    #[DataProvider('truthTableOne')]
    public function testExcludedMiddle(bool $p): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p'));
        self::assertEquals(
            $rb->create(
                $rb->logicalOr(
                    $rb['p']->equalTo(true),
                    $rb->logicalNot(
                        $rb['p']->equalTo(true)
                    )
                )
            )->evaluate($context),
            true
        );
    }

    #[DataProvider('truthTableOne')]
    public function testNonContradiction(bool $p): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('p'));
        self::assertEquals(
            $rb->create(
                $rb->logicalNot(
                    $rb->logicalAnd(
                        $rb['p']->equalTo(true),
                        $rb->logicalNot(
                            $rb['p']->equalTo(true)
                        )
                    )
                )
            )->evaluate($context),
            true
        );
    }

    /**
     * @return array<int, array<int, bool>>
     */
    public static function truthTableOne(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @return array<int, array<int, bool>>
     */
    public static function truthTableTwo(): array
    {
        return [
            [true,  true],
            [true,  false],
            [false, true],
            [false, false],
        ];
    }

    /**
     * @return array<int, array<int, bool>>
     */
    public static function truthTableThree(): array
    {
        return [
            [true,  true,  true],
            [true,  true,  false],
            [true,  false, true],
            [true,  false, false],
            [false, true,  true],
            [false, true,  false],
            [false, false, true],
            [false, false, false],
        ];
    }
}
