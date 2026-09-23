<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\RuleBuilder\Variable;
use D6N\RuleEngine\Test\Fixtures\CallCounter;
use D6N\RuleEngine\Test\Fixtures\FalseProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use PHPUnit\Framework\TestCase;

class RuleBuilderTest extends TestCase
{
    public function testManipulateVariablesViaArrayAccess(): void
    {
        $name = 'alpha';
        $rb = new RuleBuilder();

        self::assertFalse(isset($rb[$name]));

        $var = $rb[$name];
        self::assertTrue(isset($rb[$name]));

        self::assertEquals($name, $var->getName());

        self::assertSame($var, $rb[$name]);
        self::assertNull($var->getValue());

        $rb[$name] = 'eeesh.';
        self::assertEquals('eeesh.', $var->getValue());

        unset($rb[$name]);
        self::assertFalse(isset($rb[$name]));
        self::assertNotSame($var, $rb[$name]);
    }

    public function testLogicalOperatorGeneration(): void
    {
        $rb = new RuleBuilder();
        $context = new Context();

        $true = new TrueProposition();
        $false = new FalseProposition();

        self::assertFalse($rb->logicalAnd($true, $false)->evaluate($context));

        self::assertTrue($rb->logicalOr($true, $false)->evaluate($context));

        self::assertFalse($rb->logicalNot($true)->evaluate($context));

        self::assertTrue($rb->logicalXor($true, $false)->evaluate($context));
    }

    public function testRuleCreation(): void
    {
        $rb = new RuleBuilder();
        $context = new Context();

        $true = new TrueProposition();
        $false = new FalseProposition();

        self::assertTrue($rb->create($true)->evaluate($context));
        self::assertFalse($rb->create($false)->evaluate($context));

        $action = new CallCounter();
        $rule = $rb->create($true, $action);

        self::assertSame(0, $action->calls);
        $rule->execute($context);
        self::assertSame(1, $action->calls);
    }

    public function testNotAddEqualTo(): void
    {
        $rb = new RuleBuilder();
        $context = new Context([
            'A2' => 8,
            'A3' => 4,
            'B2' => 13,
        ]);

        $rule = $rb->logicalNot(
            $rb['A2']->equalTo($rb['B2'])
        );
        self::assertTrue($rule->evaluate($context));

        $rule = $rb['A2']->add($rb['A3']);

        $rule = $rb->logicalNot(
            $rule->equalTo($rb['B2'])
        );
        self::assertTrue($rule->evaluate($context));
    }

    public function testExternalOperators(): void
    {
        $rb = new RuleBuilder();
        $rb->registerOperatorNamespace('\D6N\RuleEngine\Test\Fixtures');

        $context = new Context(['a' => 100]);
        $varA = $rb['a'];

        self::assertTrue($varA->aLotGreaterThan(1)->evaluate($context)); // @phpstan-ignore method.notFound, method.nonObject (resolved by __call)

        $context['a'] = 9;
        self::assertFalse($varA->aLotGreaterThan(1)->evaluate($context)); // @phpstan-ignore method.notFound, method.nonObject (resolved by __call)
    }

    public function testLogicExceptionOnUnknownOperator(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown operator: "aLotBiggerThan"');
        $rb = new RuleBuilder();
        $rb->registerOperatorNamespace('\D6N\RuleEngine\Test\Fixtures');
        $varA = $rb['a'];

        $varA->aLotBiggerThan(1); // @phpstan-ignore method.notFound (unknown operator on purpose)
    }

    public function testSetOperatorsAcceptPlainValues(): void
    {
        $rb = new RuleBuilder();
        $context = new Context(['a' => [3]]);

        self::assertSame([3, 1, 2], $rb['a']->union([1, 2])->prepareValue($context)->getValue());
        self::assertSame([3], $rb['a']->intersect([3, 4])->prepareValue($context)->getValue());
        self::assertSame([], $rb['a']->complement([3])->prepareValue($context)->getValue());
        self::assertSame([3, 4], $rb['a']->symmetricDifference([4])->prepareValue($context)->getValue());
    }

    public function testCustomValueOperatorsCanBeChained(): void
    {
        $rb = new RuleBuilder();
        $rb->registerOperatorNamespace('\D6N\RuleEngine\Test\Fixtures');

        $plusOne = $rb['a']->plusOne(); // @phpstan-ignore method.notFound (resolved by __call)

        self::assertInstanceOf(Variable::class, $plusOne);
        self::assertTrue($plusOne->equalTo(2)->evaluate(new Context(['a' => 1])));
    }

    public function testClassesThatAreNotOperatorsAreNotResolved(): void
    {
        $rb = new RuleBuilder();
        $rb->registerOperatorNamespace('\D6N\RuleEngine\Test\Fixtures');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown operator: "notAnOperator"');

        $rb['a']->notAnOperator(); // @phpstan-ignore method.notFound (unknown operator on purpose)
    }

    public function testStringDoesNotContainInsensitive(): void
    {
        $rb = new RuleBuilder();
        $context = new Context(['name' => 'Hello World']);

        self::assertFalse($rb['name']->stringDoesNotContainInsensitive('WORLD')->evaluate($context));
        self::assertTrue($rb['name']->stringDoesNotContainInsensitive('moon')->evaluate($context));
    }

    public function testVariableNamesMustBeStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new RuleBuilder())[1]; // @phpstan-ignore expr.resultUnused (invalid input on purpose)
    }
}
