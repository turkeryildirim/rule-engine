<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\RuleBuilder;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Addition;
use D6N\RuleEngine\Operator\Ceil;
use D6N\RuleEngine\Operator\Division;
use D6N\RuleEngine\Operator\Exponentiate;
use D6N\RuleEngine\Operator\Floor;
use D6N\RuleEngine\Operator\Modulo;
use D6N\RuleEngine\Operator\Multiplication;
use D6N\RuleEngine\Operator\Negation;
use D6N\RuleEngine\Operator\Subtraction;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\RuleBuilder\Variable;
use PHPUnit\Framework\TestCase;

class VariableTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'evil';
        $var = new Variable(new RuleBuilder(), $name);
        self::assertEquals($name, $var->getName());
        self::assertNull($var->getValue());
    }

    public function testGetSetValue(): void
    {
        $values = \explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

        $variable = new Variable(new RuleBuilder(), 'technologic');
        foreach ($values as $valueString) {
            $variable->setValue($valueString);
            self::assertEquals($valueString, $variable->getValue());
        }
    }

    public function testPrepareValue(): void
    {
        $values = [
            'one'   => 'Foo',
            'two'   => 'BAR',
            'three' => static fn (): string => 'baz',
        ];

        $context = new Context($values);

        $rb = new RuleBuilder();
        $varA = new Variable($rb, 'four', 'qux');
        self::assertEquals(
            'qux',
            $varA->prepareValue($context)->getValue(),
            "Variables should return the default value if it's missing from the context."
        );

        $varB = new Variable($rb, 'one', 'FAIL');
        self::assertEquals(
            'Foo',
            $varB->prepareValue($context)->getValue()
        );

        $varC = new Variable($rb, 'three', 'FAIL');
        self::assertEquals(
            'baz',
            $varC->prepareValue($context)->getValue()
        );

        $varD = new Variable($rb, null, 'qux');
        self::assertEquals(
            'qux',
            $varD->prepareValue($context)->getValue(),
            "Anonymous variables don't require a name to prepare value"
        );
    }

    public function testFluentInterfaceHelpersAndAnonymousVariables(): void
    {
        $rb = new RuleBuilder();
        $context = new Context([
            'a' => 1,
            'b' => 2,
            'c' => [1, 4],
            'd' => [
                'foo' => 1,
                'bar' => 2,
                'baz' => [
                    'qux' => 3,
                ],
            ],
            'e' => 1.5,
        ]);

        $varA = new Variable($rb, 'a');
        $varB = new Variable($rb, 'b');
        $varC = new Variable($rb, 'c');
        $varD = new Variable($rb, 'd');
        $varE = new Variable($rb, 'e');

        self::assertTrue($varA->greaterThan(0)->evaluate($context));
        self::assertFalse($varA->greaterThan(2)->evaluate($context));

        self::assertTrue($varA->greaterThanOrEqualTo(0)->evaluate($context));
        self::assertTrue($varA->greaterThanOrEqualTo(1)->evaluate($context));
        self::assertFalse($varA->greaterThanOrEqualTo(2)->evaluate($context));

        self::assertTrue($varA->lessThan(2)->evaluate($context));
        self::assertFalse($varA->lessThan(0)->evaluate($context));

        self::assertTrue($varA->lessThanOrEqualTo(1)->evaluate($context));
        self::assertTrue($varA->lessThanOrEqualTo(2)->evaluate($context));
        self::assertFalse($varA->lessThanOrEqualTo(0)->evaluate($context));

        self::assertTrue($varA->equalTo(1)->evaluate($context));
        self::assertFalse($varA->equalTo(0)->evaluate($context));
        self::assertFalse($varA->equalTo(2)->evaluate($context));

        self::assertFalse($varA->notEqualTo(1)->evaluate($context));
        self::assertTrue($varA->notEqualTo(0)->evaluate($context));
        self::assertTrue($varA->notEqualTo(2)->evaluate($context));

        self::assertInstanceOf(Addition::class, $varA->add(3)->getValue());
        self::assertEquals(4, $varA->add(3)->prepareValue($context)->getValue());
        self::assertEquals(0, $varA->add(-1)->prepareValue($context)->getValue());

        self::assertInstanceOf(Ceil::class, $varE->ceil()->getValue());
        self::assertEquals(2, $varE->ceil()->prepareValue($context)->getValue());

        self::assertInstanceOf(Division::class, $varB->divide(3)->getValue());
        self::assertEquals(1, $varB->divide(2)->prepareValue($context)->getValue());
        self::assertEquals(-2, $varB->divide(-1)->prepareValue($context)->getValue());

        self::assertInstanceOf(Floor::class, $varE->floor()->getValue());
        self::assertEquals(1, $varE->floor()->prepareValue($context)->getValue());

        self::assertInstanceOf(Modulo::class, $varA->modulo(3)->getValue());
        self::assertEquals(1, $varA->modulo(3)->prepareValue($context)->getValue());
        self::assertEquals(0, $varB->modulo(2)->prepareValue($context)->getValue());

        self::assertInstanceOf(Multiplication::class, $varA->multiply(3)->getValue());
        self::assertEquals(6, $varB->multiply(3)->prepareValue($context)->getValue());
        self::assertEquals(-2, $varB->multiply(-1)->prepareValue($context)->getValue());

        self::assertInstanceOf(Negation::class, $varA->negate()->getValue());
        self::assertEquals(-1, $varA->negate()->prepareValue($context)->getValue());
        self::assertEquals(-2, $varB->negate()->prepareValue($context)->getValue());

        self::assertInstanceOf(Subtraction::class, $varA->subtract(3)->getValue());
        self::assertEquals(-2, $varA->subtract(3)->prepareValue($context)->getValue());
        self::assertEquals(2, $varA->subtract(-1)->prepareValue($context)->getValue());

        self::assertInstanceOf(Exponentiate::class, $varA->exponentiate(3)->getValue());
        self::assertEquals(1, $varA->exponentiate(3)->prepareValue($context)->getValue());
        self::assertEquals(1, $varA->exponentiate(-1)->prepareValue($context)->getValue());
        self::assertEquals(8, $varB->exponentiate(3)->prepareValue($context)->getValue());
        self::assertEquals(0.5, $varB->exponentiate(-1)->prepareValue($context)->getValue());

        self::assertFalse($varA->greaterThan($varB)->evaluate($context));
        self::assertTrue($varA->lessThan($varB)->evaluate($context));

        self::assertTrue($varC->setContains($varA)->evaluate($context));

        self::assertTrue($varC->setDoesNotContain($varB)->evaluate($context));

        self::assertEquals($varD['foo']->getName(), 'foo');
        self::assertTrue($varD['foo']->equalTo(1)->evaluate($context));

        self::assertEquals($varD['bar']->getName(), 'bar');
        self::assertTrue($varD['bar']->equalTo(2)->evaluate($context));

        self::assertEquals($varD['baz']['qux']->getName(), 'qux');
        self::assertTrue($varD['baz']['qux']->equalTo(3)->evaluate($context));
    }

    public function testArrayAccess(): void
    {
        $var = new Variable(new RuleBuilder());

        $foo = $var['foo'];
        $bar = $var['bar'];

        self::assertSame($var['foo'], $foo);
        self::assertSame($var['bar'], $bar);
        self::assertNotSame($foo, $bar);

        self::assertTrue($var->offsetExists('foo'));
        self::assertTrue($var->offsetExists('bar'));

        self::assertFalse($var->offsetExists('baz'));
        self::assertFalse($var->offsetExists('qux'));

        $baz = $var->getProperty('baz');
        self::assertTrue($var->offsetExists('baz'));

        $qux = $var['qux'];
        self::assertTrue($var->offsetExists('qux'));

        unset($var['foo'], $var['bar'], $var['baz']);

        self::assertFalse($var->offsetExists('foo'));
        self::assertFalse($var->offsetExists('bar'));
        self::assertFalse($var->offsetExists('baz'));
        self::assertTrue($var->offsetExists('qux'));
    }

    public function testRemainingFluentOperators(): void
    {
        $rb = new RuleBuilder();
        $context = new Context(['n' => 1, 'set' => [3, 1, 2]]);

        self::assertTrue($rb['n']->sameAs('1')->evaluate($context));
        self::assertFalse($rb['n']->notSameAs(1.0)->evaluate($context));
        self::assertSame(1, $rb['set']->min()->prepareValue($context)->getValue());
        self::assertSame(3, $rb['set']->max()->prepareValue($context)->getValue());
        self::assertTrue($rb['set']->doesNotContainSubset([4])->evaluate($context));
    }

    public function testPropertyDefaultsCanBeAssigned(): void
    {
        $rb = new RuleBuilder();
        $rb['user']->offsetSet('roles', ['anonymous']);

        self::assertSame(['anonymous'], $rb['user']['roles']->prepareValue(new Context(['user' => null]))->getValue());
    }
}
