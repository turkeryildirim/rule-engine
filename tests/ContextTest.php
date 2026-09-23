<?php

declare(strict_types=1);

/*
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Test\Fixtures\Fact;
use D6N\RuleEngine\Test\Fixtures\Invokable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Ruler Context test.
 *
 * Derived from Pimple, by Fabien Potencier:
 *
 * https://github.com/fabpot/Pimple
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Justin Hileman <justin@justinhileman.info>
 */
class ContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $facts = [
            'name'      => 'Mint Chip',
            'type'      => 'Ice Cream',
            'delicious' => static fn (): true => true,
        ];

        $context = new Context($facts);

        self::assertTrue(isset($context['name']));
        self::assertEquals('Mint Chip', $context['name']);

        self::assertTrue(isset($context['type']));
        self::assertEquals('Ice Cream', $context['type']);

        self::assertTrue(isset($context['delicious']));
        self::assertTrue($context['delicious']);
    }

    public function testWithString(): void
    {
        $context = new Context();
        $context['param'] = 'value';

        self::assertEquals('value', $context['param']);
    }

    public function testWithClosure(): void
    {
        $context = new Context(['fact' => static fn (): Fact => new Fact()]);

        self::assertInstanceOf(Fact::class, $context['fact']);
    }

    public function testFactsShouldBeDifferent(): void
    {
        $context = new Context(['fact' => static fn (): Fact => new Fact()]);

        $factOne = $context['fact'];
        self::assertInstanceOf(Fact::class, $factOne);

        $factTwo = $context['fact'];
        self::assertInstanceOf(Fact::class, $factTwo);

        self::assertNotSame($factOne, $factTwo);
    }

    public function testShouldPassContextAsParameter(): void
    {
        $context = new Context();
        $context['fact'] = (static fn (): Fact => new Fact());
        $context['context'] = (static fn ($context) => $context);

        self::assertNotSame($context, $context['fact']);
        self::assertSame($context, $context['context']);
    }

    public function testIsset(): void
    {
        $context = new Context();
        $context['param'] = 'value';
        $context['fact'] = (static fn (): Fact => new Fact());

        $context['null'] = null;

        self::assertTrue(isset($context['param']));
        self::assertTrue(isset($context['fact']));
        self::assertTrue(isset($context['null']));
        self::assertFalse(isset($context['non_existent']));
    }

    public function testConstructorInjection(): void
    {
        $params = ['param' => 'value'];
        $context = new Context($params);

        self::assertSame($params['param'], $context['param']);
    }

    public function testOffsetGetValidatesKeyIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fact "foo" is not defined.');
        $context = new Context();
        $context->offsetGet('foo');
    }

    public function testOffsetGetHonorsNullValues(): void
    {
        $context = new Context();
        $context['foo'] = null;
        self::assertNull($context['foo']);
    }

    public function testUnset(): void
    {
        $context = new Context();
        $context['param'] = 'value';
        $context['fact'] = (static fn (): Fact => new Fact());

        unset($context['param'], $context['fact']);
        self::assertFalse(isset($context['param']));
        self::assertFalse(isset($context['fact']));
    }

    #[DataProvider('factDefinitionProvider')]
    public function testShare(\Closure|Invokable $fact): void
    {
        $context = new Context();
        $context->offsetSet('shared_fact', $context->share($fact));

        $factOne = $context['shared_fact'];
        self::assertInstanceOf(Fact::class, $factOne);

        $factTwo = $context['shared_fact'];
        self::assertInstanceOf(Fact::class, $factTwo);

        self::assertSame($factOne, $factTwo);
    }

    #[DataProvider('factDefinitionProvider')]
    public function testProtect(\Closure|Invokable $fact): void
    {
        $context = new Context();
        $context['protected'] = $context->protect($fact);

        self::assertSame($fact, $context['protected']);
    }

    public function testGlobalFunctionNameAsParameterValue(): void
    {
        $context = new Context();
        $context['global_function'] = 'strlen';
        self::assertSame('strlen', $context['global_function']);
    }

    public function testRaw(): void
    {
        $context = new Context();
        $context['fact'] = $definition = (static fn (): string => 'foo');
        self::assertSame($definition, $context->raw('fact'));
    }

    public function testRawHonorsNullValues(): void
    {
        $context = new Context();
        $context['foo'] = null;
        self::assertNull($context->raw('foo'));
    }

    public function testRawValidatesKeyIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fact "foo" is not defined.');
        $context = new Context();
        $context->raw('foo');
    }

    public function testKeys(): void
    {
        $context = new Context();
        $context['foo'] = 123;
        $context['bar'] = 123;

        self::assertEquals(['foo', 'bar'], $context->keys());
    }

    #[Test]
    public function settingAnInvokableObjectShouldTreatItAsFactory(): void
    {
        $context = new Context(['invokable' => new Invokable()]);

        self::assertInstanceOf(Fact::class, $context['invokable']);
    }

    #[Test]
    public function settingNonInvokableObjectShouldTreatItAsParameter(): void
    {
        $context = new Context();
        $context['non_invokable'] = $fact = new Fact();

        self::assertSame($fact, $context['non_invokable']);
    }

    #[DataProvider('badFactDefinitionProvider')]
    public function testShareFailsForInvalidFactDefinitions(int|Fact $fact): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value is not a Closure or invokable object.');
        $context = new Context();
        $context->share($fact);
    }

    #[DataProvider('badFactDefinitionProvider')]
    public function testProtectFailsForInvalidFactDefinitions(int|Fact $fact): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Callable is not a Closure or invokable object.');
        $context = new Context();
        $context->protect($fact);
    }

    /**
     * Provider for invalid fact definitions.
     *
     * @return array<int, int[]|Fact[]>
     */
    public static function badFactDefinitionProvider(): array
    {
        return [
            [123],
            [new Fact()],
        ];
    }

    /**
     * Provider for fact definitions.
     *
     * @return array<int, (\Closure(mixed $value): Fact)[]|Invokable[]>
     */
    public static function factDefinitionProvider(): array
    {
        return [
            [static function ($value): Fact {
                $fact = new Fact();
                $fact->value = $value;

                return $fact;
            }],
            [new Invokable()],
        ];
    }

    public function testFactNamesMustBeStringsOrIntegers(): void
    {
        $context = new Context();

        self::assertFalse($context->offsetExists(new \stdClass())); // @phpstan-ignore method.impossibleType (runtime guard for untyped callers)

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fact names must be strings or integers.');

        $context->offsetSet(new \stdClass(), 'value');
    }

    public function testUnsettingAnUndefinedFactIsANoOp(): void
    {
        $context = new Context(['kept' => 1]);

        $context->offsetUnset('missing');

        self::assertSame(['kept'], $context->keys());
    }
}
