<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator\EqualTo;
use D6N\RuleEngine\Operator\LogicalAnd;
use D6N\RuleEngine\Operator\LogicalOperator;
use D6N\RuleEngine\OperatorRegistry;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\RuleBuilder\Variable;
use D6N\RuleEngine\Test\Fixtures\ALotGreaterThan;
use D6N\RuleEngine\Test\Fixtures\PlusOne;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use D6N\RuleEngine\Variable as BaseVariable;
use PHPUnit\Framework\TestCase;

class OperatorRegistryTest extends TestCase
{
    public function testResolvesBuiltInsByName(): void
    {
        $registry = new OperatorRegistry();

        self::assertSame(EqualTo::class, $registry->resolve('equalTo'));
        self::assertSame('equalTo', $registry->nameOf(new EqualTo(new BaseVariable(), new BaseVariable())));
        self::assertSame('logicalAnd', $registry->nameOf(new LogicalAnd()));
    }

    public function testResolvesOperatorsInRegisteredNamespaces(): void
    {
        $registry = new OperatorRegistry();
        $registry->registerNamespace('\D6N\RuleEngine\Test\Fixtures\\');

        self::assertSame(ALotGreaterThan::class, $registry->resolve('aLotGreaterThan'));
        self::assertSame('plusOne', $registry->nameOf(new PlusOne(new BaseVariable())));
    }

    public function testExplicitRegistrationTakesAName(): void
    {
        $registry = new OperatorRegistry();
        $registry->register('always', TrueProposition::class);

        self::assertSame(TrueProposition::class, $registry->resolve('always'));
        self::assertSame('always', $registry->nameOf(new TrueProposition()));
    }

    public function testOnlyOperatorsCanBeRegistered(): void
    {
        $this->expectException(UnknownOperatorException::class);
        $this->expectExceptionMessage('Cannot register "nope": stdClass is not a Proposition or VariableOperand.');

        new OperatorRegistry()->register('nope', \stdClass::class);
    }

    public function testUnknownNamesAreRejected(): void
    {
        $this->expectException(UnknownOperatorException::class);
        $this->expectExceptionMessage('Unknown operator: "noSuchOperator"');

        new OperatorRegistry()->resolve('noSuchOperator');
    }

    public function testUnregisteredOperatorsHaveNoName(): void
    {
        $this->expectException(UnknownOperatorException::class);
        $this->expectExceptionMessage('Operator '.TrueProposition::class.' is not registered');

        new OperatorRegistry()->nameOf(new TrueProposition());
    }

    public function testAShortNameThatResolvesToAnotherClassIsNotUsed(): void
    {
        // "trueProposition" is not resolvable at all, and "equalTo" belongs to the built-in
        $registry = new OperatorRegistry();
        $registry->register('equalTo', TrueProposition::class);

        $this->expectException(UnknownOperatorException::class);

        $registry->nameOf(new EqualTo(new BaseVariable(), new BaseVariable()));
    }

    public function testRuleBuilderSharesItsRegistry(): void
    {
        $registry = new OperatorRegistry();
        $rb = new RuleBuilder($registry);
        $rb->registerOperatorNamespace('D6N\RuleEngine\Test\Fixtures');

        self::assertSame($registry, $rb->getOperatorRegistry());
        self::assertSame(ALotGreaterThan::class, $registry->resolve('aLotGreaterThan'));
    }

    public function testEveryFluentBuiltInIsDocumentedOnTheVariable(): void
    {
        $doc = (string) new \ReflectionClass(Variable::class)->getDocComment();
        \preg_match_all('/@method\s+\S+\s+(\w+)\(/', $doc, $matches);

        $fluent = \array_keys(\array_filter(
            OperatorRegistry::BUILT_INS,
            static fn (string $class): bool => !\is_subclass_of($class, LogicalOperator::class),
        ));

        self::assertEqualsCanonicalizing($fluent, $matches[1]);
    }
}
