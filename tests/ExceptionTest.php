<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\ArithmeticException;
use D6N\RuleEngine\Exception\DivisionByZeroException;
use D6N\RuleEngine\Exception\FrozenFactException;
use D6N\RuleEngine\Exception\InvalidNameException;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Exception\NotCallableException;
use D6N\RuleEngine\Exception\OperandCountException;
use D6N\RuleEngine\Exception\RuleEngineException;
use D6N\RuleEngine\Exception\UndefinedFactException;
use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator\EqualTo;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Set;
use D6N\RuleEngine\Value;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    /**
     * @param \Closure(): mixed                 $trigger
     * @param class-string<RuleEngineException> $specific
     * @param class-string<\Throwable>          $legacy   the SPL exception thrown before these classes existed
     */
    #[DataProvider('failures')]
    public function testThrowsSpecificExceptionThatKeepsItsLegacyType(\Closure $trigger, string $specific, string $legacy): void
    {
        try {
            $trigger();
            self::fail('Expected an exception.');
        } catch (RuleEngineException $e) {
            self::assertInstanceOf($specific, $e);
            self::assertInstanceOf($legacy, $e);
        }
    }

    /**
     * @return iterable<string, array{\Closure(): mixed, class-string<RuleEngineException>, class-string<\Throwable>}>
     */
    public static function failures(): iterable
    {
        yield 'non-numeric arithmetic' => [
            static fn (): int|float => new Value('a')->add(new Value(1)),
            ArithmeticException::class,
            \RuntimeException::class,
        ];
        yield 'division by zero' => [
            static fn (): int|float => new Value(1)->divide(new Value(0)),
            DivisionByZeroException::class,
            \RuntimeException::class,
        ];
        yield 'non-numeric set minimum' => [
            static fn (): mixed => new Set(['a'])->min(),
            ArithmeticException::class,
            \RuntimeException::class,
        ];
        yield 'string operation on array' => [
            static fn (): bool => new Value([])->stringContains(new Value('a')),
            InvalidOperandException::class,
            \RuntimeException::class,
        ];
        yield 'undefined fact' => [
            static fn (): mixed => new Context()->offsetGet('missing'),
            UndefinedFactException::class,
            \InvalidArgumentException::class,
        ];
        yield 'frozen fact' => [
            static function (): void {
                $context = new Context();
                $context->offsetSet('fact', $context->share(static fn (): int => 1));
                $context->offsetGet('fact');
                $context->offsetSet('fact', 2);
            },
            FrozenFactException::class,
            \RuntimeException::class,
        ];
        yield 'invalid fact name' => [
            static fn () => new Context()->offsetSet(1.5, 'value'),
            InvalidNameException::class,
            \InvalidArgumentException::class,
        ];
        yield 'invalid variable name' => [
            static fn (): mixed => new RuleBuilder()->offsetGet(1),
            InvalidNameException::class,
            \InvalidArgumentException::class,
        ];
        yield 'invalid property name' => [
            static fn (): mixed => new RuleBuilder()->offsetGet('a')->offsetGet(1),
            InvalidNameException::class,
            \InvalidArgumentException::class,
        ];
        yield 'sharing a non-callable' => [
            static fn (): object => new Context()->share('strlen'),
            NotCallableException::class,
            \InvalidArgumentException::class,
        ];
        yield 'protecting a non-callable' => [
            static fn (): object => new Context()->protect(1),
            NotCallableException::class,
            \InvalidArgumentException::class,
        ];
        yield 'too many operands' => [
            static fn (): EqualTo => new EqualTo(new Variable(), new Variable(), new Variable()),
            OperandCountException::class,
            \LogicException::class,
        ];
        yield 'too few operands' => [
            static fn (): array => new EqualTo(new Variable())->getOperands(),
            OperandCountException::class,
            \LogicException::class,
        ];
        yield 'unknown operator' => [
            static fn (): string => new RuleBuilder()->findOperator('noSuchOperator'),
            UnknownOperatorException::class,
            \LogicException::class,
        ];
    }
}
