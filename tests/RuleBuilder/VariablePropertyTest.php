<?php

declare(strict_types=1);

namespace Ruler\Test\RuleBuilder;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\RuleBuilder;
use Ruler\RuleBuilder\Variable;
use Ruler\RuleBuilder\VariableProperty;

class VariablePropertyTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'evil';
        $prop = new VariableProperty(new Variable(new RuleBuilder()), $name);
        self::assertEquals($name, $prop->getName());
        self::assertNull($prop->getValue());
    }

    public function testGetSetValue(): void
    {
        $values = \explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

        $prop = new VariableProperty(new Variable(new RuleBuilder()), 'technologic');
        foreach ($values as $valueString) {
            $prop->setValue($valueString);
            self::assertEquals($valueString, $prop->getValue());
        }
    }

    public function testPrepareValue(): void
    {
        $values = [
            'root' => [
                'one' => 'Foo',
                'two' => 'BAR',
            ],
        ];

        $context = new Context($values);

        $var = new Variable(new RuleBuilder(), 'root');

        $propA = new VariableProperty($var, 'undefined', 'default');
        self::assertEquals(
            'default',
            $propA->prepareValue($context)->getValue(),
            "VariableProperties should return the default value if it's missing from the context."
        );

        $propB = new VariableProperty($var, 'one', 'FAIL');
        self::assertEquals(
            'Foo',
            $propB->prepareValue($context)->getValue()
        );
    }

    public function testFluentInterfaceHelpersAndAnonymousVariables(): void
    {
        $context = new Context([
            'root' => [
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
                'e' => 'string',
                'f' => 'ring',
                'g' => 'stuff',
                'h' => 'STRING',
            ],
        ]);

        $root = new Variable(new RuleBuilder(), 'root');

        $varA = $root['a'];
        $varB = $root['b'];
        $varC = $root['c'];
        $varD = $root['d'];
        $varE = $root['e'];
        $varF = $root['f'];
        $varG = $root['g'];
        $varH = $root['h'];

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

        self::assertFalse($varA->greaterThan($varB)->evaluate($context));
        self::assertTrue($varA->lessThan($varB)->evaluate($context));

        self::assertTrue($varE->stringContains($varF)->evaluate($context));

        self::assertTrue($varE->stringContainsInsensitive($varH)->evaluate($context));

        self::assertTrue($varE->stringDoesNotContain($varG)->evaluate($context));

        self::assertTrue($varC->setContains($varA)->evaluate($context));

        self::assertTrue($varC->setDoesNotContain($varB)->evaluate($context));

        self::assertEquals($varD['foo']->getName(), 'foo');
        self::assertTrue($varD['foo']->equalTo(1)->evaluate($context));

        self::assertEquals($varD['bar']->getName(), 'bar');
        self::assertTrue($varD['bar']->equalTo(2)->evaluate($context));

        self::assertEquals($varD['baz']['qux']->getName(), 'qux');
        self::assertTrue($varD['baz']['qux']->equalTo(3)->evaluate($context));

        self::assertTrue($varE->endsWith($varE)->evaluate($context));

        self::assertTrue($varE->endsWithInsensitive($varE)->evaluate($context));

        self::assertTrue($varE->startsWith($varE)->evaluate($context));

        self::assertTrue($varE->startsWithInsensitive($varE)->evaluate($context));
    }
}
