<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Variable;
use D6N\RuleEngine\VariableProperty;
use PHPUnit\Framework\TestCase;

class VariablePropertyTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'evil';
        $prop = new VariableProperty(new Variable(), $name);
        self::assertEquals($name, $prop->getName());
        self::assertNull($prop->getValue());
    }

    public function testGetSetValue(): void
    {
        $values = \explode(', ', 'Plug it, play it, burn it, rip it, drag and drop it, zip, unzip it');

        $prop = new VariableProperty(new Variable(), 'technologic');
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

        $var = new Variable('root');

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

    public function testNonPublicMethodsFallBackToTheDefault(): void
    {
        $parent = new Variable('object', new class {
            public function visible(): string
            {
                return 'public';
            }

            private function hidden(): string // @phpstan-ignore method.unused (must not be reachable)
            {
                return 'private';
            }
        });
        $context = new Context();

        self::assertSame('public', new VariableProperty($parent, 'visible')->prepareValue($context)->getValue());
        self::assertSame('default', new VariableProperty($parent, 'hidden', 'default')->prepareValue($context)->getValue());
    }

    public function testMagicPropertiesAreResolved(): void
    {
        $parent = new Variable('object', new class {
            public function __isset(string $name): bool
            {
                return 'magic' === $name;
            }

            public function __get(string $name): string
            {
                return 'via __get';
            }
        });

        self::assertSame('via __get', new VariableProperty($parent, 'magic')->prepareValue(new Context())->getValue());
    }

    public function testMagicCallDoesNotShadowProperties(): void
    {
        $parent = new Variable('object', new class {
            public string $name = 'from property';

            /**
             * @param array<mixed> $arguments
             */
            public function __call(string $method, array $arguments): never
            {
                throw new \BadMethodCallException($method);
            }
        });

        self::assertSame('from property', new VariableProperty($parent, 'name')->prepareValue(new Context())->getValue());
    }

    public function testArrayAccessOffsetsAreResolved(): void
    {
        $parent = new Variable('object', new \ArrayObject(['key' => 'from offset']));

        self::assertSame('from offset', new VariableProperty($parent, 'key')->prepareValue(new Context())->getValue());
    }
}
