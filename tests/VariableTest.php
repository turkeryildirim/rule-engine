<?php

declare(strict_types=1);

namespace Ruler\Test;

use PHPUnit\Framework\TestCase;
use Ruler\Context;
use Ruler\Variable;

class VariableTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'evil';
        $var = new Variable($name);
        self::assertEquals($name, $var->getName());
        self::assertNull($var->getValue());
    }

    public function testGetSetValue(): void
    {
        $values = \explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

        $variable = new Variable('technologic');
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

        $varA = new Variable('four', 'qux');
        self::assertEquals(
            'qux',
            $varA->prepareValue($context)->getValue(),
            "Variables should return the default value if it's missing from the context."
        );

        $varB = new Variable('one', 'FAIL');
        self::assertEquals(
            'Foo',
            $varB->prepareValue($context)->getValue()
        );

        $varC = new Variable('three', 'FAIL');
        self::assertEquals(
            'baz',
            $varC->prepareValue($context)->getValue()
        );

        $varD = new Variable(null, 'qux');
        self::assertEquals(
            'qux',
            $varD->prepareValue($context)->getValue(),
            "Anonymous variables don't require a name to prepare value"
        );
    }

    public function testAnonymousVariableHasNoName(): void
    {
        self::assertNull(new Variable(null, 5)->getName());
    }
}
