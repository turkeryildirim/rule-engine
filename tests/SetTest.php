<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Set;
use D6N\RuleEngine\Test\Fixtures\toStringable;
use D6N\RuleEngine\Value;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testNonStringableObject(): void
    {
        $setExpected = [
            new \stdClass(),
            new \stdClass(),
        ];
        $set = new Set($setExpected);
        self::assertEquals(2, \count($set));
    }

    public function testObjectUniqueness(): void
    {
        $objectA = new \stdClass();
        $objectA->something = 'else';
        $objectB = new \stdClass();
        $objectB->foo = 'bar';

        $set = new Set([
            $objectA,
            $objectB,
        ]);

        self::assertEquals(2, \count($set));
        self::assertTrue($set->setContains(new Value($objectA)));
        self::assertTrue($set->setContains(new Value($objectB)));
        self::assertFalse($set->setContains(new Value(false)));
    }

    public function testStringable(): void
    {
        $set = new Set([
            $one = new toStringable(1),
            $two = new toStringable(2),
            $too = new toStringable(2),
        ]);

        // Objects are members by identity, even when they cast to the same string.
        self::assertCount(3, $set);
        self::assertTrue($set->setContains(new Value($one)));
        self::assertTrue($set->setContains(new Value($two)));
        self::assertTrue($set->setContains(new Value($too)));
        self::assertFalse($set->setContains(new Value(new toStringable(2))));
        self::assertFalse($set->setContains(new Value(2)));
    }

    public function testNestedSetsDoNotCollide(): void
    {
        self::assertCount(2, new Set([[1, 23], [12, 3]]));
    }

    public function testMembershipIsTypeSensitive(): void
    {
        $set = new Set([1, '1', 1.0, true, null, '', false]);

        self::assertCount(7, $set);
        self::assertTrue($set->setContains(new Value('1')));
        self::assertFalse($set->setContains(new Value(2)));
        self::assertFalse(new Set([1, 2])->containsSubset(new Set(['1'])));
    }

    public function testNestedSetsAreComparedByContent(): void
    {
        $set = new Set([[1, 2]]);

        self::assertTrue($set->setContains(new Value([2, 1])));
        self::assertTrue($set->setContains(new Value([1, 2, 2])));
        self::assertFalse($set->setContains(new Value([1])));
    }

    public function testSetOperationsWorkWithObjects(): void
    {
        $shared = new \stdClass();
        $left = new Set([$shared, new \stdClass()]);
        $right = new Set([$shared, new \stdClass()]);

        self::assertCount(3, $left->union($right));
        self::assertSame([$shared], $left->intersect($right)->getValue());
        self::assertCount(1, $left->complement($right));
        self::assertCount(2, $left->symmetricDifference($right));
    }

    public function testKeysAreDiscarded(): void
    {
        self::assertSame(['a', 'b'], new Set(['x' => 'a', 'y' => 'b'])->getValue());
    }
}
