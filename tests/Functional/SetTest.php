<?php

declare(strict_types=1);

namespace Ruler\Test\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\RuleBuilder;

class SetTest extends TestCase
{
    public function testComplicated(): void
    {
        $rb = new RuleBuilder();
        $context = new Context([
            'expected' => 'a',
            'foo'      => ['a', 'z'],
            'bar'      => ['z', 'b'],
            'baz'      => ['a', 'z', 'b', 'q'],
            'bob'      => ['a', 'd'],
        ]);

        self::assertTrue(
            $rb->create(
                $rb['foo']->intersect(
                    $rb['bar']->symmetricDifference($rb['baz'])
                )->setContains($rb['expected'])
            )->evaluate($context)
        );

        self::assertTrue(
            $rb->create(
                $rb['bar']->union(
                    $rb['bob']
                )->containsSubset($rb['foo'])
            )->evaluate($context)
        );
    }

    /**
     * @return array<int, never[][]|string[][]>
     */
    public static function setUnion(): array
    {
        return [
            [
                ['a', 'b', 'c'],
                [],
                ['a', 'b', 'c'],
            ],
            [
                [],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['b', 'c', 'd'],
            ],
        ];
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $expected
     */
    #[DataProvider('setUnion')]
    public function testUnion(array $a, array $b, array $expected): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('a', 'b', 'expected'));
        self::assertTrue(
            $rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->union($rb['b'])
                )
            )->evaluate($context)
        );
    }

    /**
     * @return array<int, string[][]>
     */
    public static function setIntersect(): array
    {
        return [
            [
                ['a', 'b', 'c'],
                [],
                [],
            ],
            [
                [],
                ['a', 'b', 'c'],
                [],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['b', 'c'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['b'],
            ],
        ];
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $expected
     */
    #[DataProvider('setIntersect')]
    public function testIntersect(array $a, array $b, array $expected): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('a', 'b', 'expected'));
        self::assertTrue(
            $rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->intersect($rb['b'])
                )
            )->evaluate($context)
        );
    }

    /**
     * @return array<int, string[][]>
     */
    public static function setComplement(): array
    {
        return [
            [
                ['a', 'b', 'c'],
                [],
                ['a', 'b', 'c'],
            ],
            [
                [],
                ['a', 'b', 'c'],
                [],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                ['a', 'b', 'c'],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['a'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['c'],
            ],
        ];
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $expected
     */
    #[DataProvider('setComplement')]
    public function testComplement(array $a, array $b, array $expected): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('a', 'b', 'expected'));
        self::assertTrue(
            $rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->complement($rb['b'])
                )
            )->evaluate($context)
        );
    }

    /**
     * @return array<int, string[][]|never[][]>
     */
    public static function setSymmetricDifference(): array
    {
        return [
            [
                ['a', 'b', 'c'],
                [],
                ['a', 'b', 'c'],
            ],
            [
                [],
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            [
                [],
                [],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['d', 'e', 'f'],
                ['a', 'b', 'c', 'd', 'e', 'f'],
            ],
            [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
                [],
            ],
            [
                ['a', 'b', 'c'],
                ['b', 'c'],
                ['a'],
            ],
            [
                ['b', 'c'],
                ['b', 'd'],
                ['c', 'd'],
            ],
        ];
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @param array<mixed> $expected
     */
    #[DataProvider('setSymmetricDifference')]
    public function testSymmetricDifference(array $a, array $b, array $expected): void
    {
        $rb = new RuleBuilder();
        $context = new Context(\compact('a', 'b', 'expected'));
        self::assertTrue(
            $rb->create(
                $rb['expected']->equalTo(
                    $rb['a']->symmetricDifference($rb['b'])
                )
            )->evaluate($context)
        );
    }
}
