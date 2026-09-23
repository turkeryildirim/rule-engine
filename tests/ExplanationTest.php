<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\DivisionByZeroException;
use D6N\RuleEngine\Exception\EvaluationException;
use D6N\RuleEngine\Exception\UndefinedFactException;
use D6N\RuleEngine\Explainer;
use D6N\RuleEngine\Explanation;
use D6N\RuleEngine\Operator\EqualTo;
use D6N\RuleEngine\OperatorRegistry;
use D6N\RuleEngine\Rule;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\Test\Fixtures\CallbackProposition;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use D6N\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class ExplanationTest extends TestCase
{
    public function testExplainsEveryNode(): void
    {
        $rb = new RuleBuilder();
        $vip = $rb->create($rb['user']['tier']->equalTo('vip'), name: 'vip');
        $rule = $rb->create(
            $rb->logicalOr($vip, $rb['total']->multiply(2)->greaterThan(100)),
            name: 'bigSpender',
        );

        $explanation = $rule->explain(new Context(['user' => ['tier' => 'basic'], 'total' => 60]));

        self::assertSame(<<<'TEXT'
            rule bigSpender: true
              logicalOr: true
                rule vip: false
                  equalTo: false
                    property tier: "basic"
                      var user: {"tier":"basic"}
                    value: "vip"
                greaterThan: true
                  multiply: 120
                    var total: 60
                    value: 2
                  value: 100
            TEXT, (string) $explanation);

        self::assertFalse($explanation->failed());
        self::assertSame([], $explanation->failureTrail());
        self::assertSame('condition.operands[0].condition.operands[0].of', $explanation->children[0]->children[0]->children[0]->children[0]->children[0]->path);
    }

    public function testArrayAndJsonFormsMatch(): void
    {
        $rb = new RuleBuilder();
        $explanation = $rb->create($rb['a']->equalTo(1))->explain(new Context(['a' => 1]));

        self::assertSame([
            'path'     => '',
            'type'     => 'rule',
            'name'     => null,
            'result'   => true,
            'children' => [[
                'path'     => 'condition',
                'type'     => 'operator',
                'name'     => 'equalTo',
                'result'   => true,
                'children' => [
                    ['path' => 'condition.operands[0]', 'type' => 'var', 'name' => 'a', 'result' => 1],
                    ['path' => 'condition.operands[1]', 'type' => 'value', 'name' => null, 'result' => 1],
                ],
            ]],
        ], $explanation->toArray());
        self::assertSame(\json_encode($explanation->toArray()), \json_encode($explanation));
    }

    public function testValuesAreDescribedSafely(): void
    {
        $date = new \DateTimeImmutable('2026-01-02 03:04:05 UTC');
        $explanation = new Explanation('', 'value', null, [$date, new \stdClass(), \INF, [1.0]]);

        self::assertSame(['2026-01-02T03:04:05+00:00', 'stdClass', 'INF', [1.0]], $explanation->toArray()['result']);
        self::assertSame('value: ["2026-01-02T03:04:05+00:00","stdClass","INF",[1.0]]', (string) $explanation);
    }

    public function testFailingRulesReportWhereTheyFailed(): void
    {
        $rb = new RuleBuilder();
        $rule = $rb->create(
            $rb->logicalAnd($rb['a']->equalTo(1), $rb['total']->divide($rb['count'])->greaterThan(10)),
            name: 'average',
        );

        try {
            $rule->evaluate(new Context(['a' => 1, 'total' => 50, 'count' => 0]));
            self::fail('Expected an exception.');
        } catch (EvaluationException $e) {
            self::assertSame('Rule "average" failed at condition.operands[1].operands[0] (logicalAnd > greaterThan > divide): Division by zero', $e->getMessage());
            self::assertSame('average', $e->getRuleName());
            self::assertSame('condition.operands[1].operands[0]', $e->getFailurePath());
            self::assertInstanceOf(DivisionByZeroException::class, $e->getPrevious());
            self::assertStringContainsString('divide: ERROR Division by zero', (string) $e->getExplanation());
            self::assertStringContainsString('var count: 0', (string) $e->getExplanation());
            self::assertSame('Division by zero', $e->getExplanation()->toArray()['error'] ?? null);
        }
    }

    public function testErrorsInFactsAreReportedOnTheVariable(): void
    {
        $rb = new RuleBuilder();
        $context = new Context(['user' => static fn (): never => throw new \RuntimeException('database is down')]);

        try {
            $rb->create($rb['user']->isNull(), name: 'anonymous')->evaluate($context);
            self::fail('Expected an exception.');
        } catch (EvaluationException $e) {
            self::assertSame('Rule "anonymous" failed at condition.operands[0] (isNull > user): database is down', $e->getMessage());
        }
    }

    public function testNestedRuleFailuresAreReportedOnceWithTheOriginalCause(): void
    {
        $rb = new RuleBuilder();
        $inner = $rb->create($rb['x']->divide(0)->greaterThan(1), name: 'inner');
        $outer = $rb->create($rb->logicalNot($inner));

        try {
            $outer->evaluate(new Context(['x' => 1]));
            self::fail('Expected an exception.');
        } catch (EvaluationException $e) {
            self::assertSame('Rule (unnamed) failed at condition.operands[0].condition.operands[0] (logicalNot > inner > greaterThan > divide): Division by zero', $e->getMessage());
            self::assertInstanceOf(DivisionByZeroException::class, $e->getPrevious());
            self::assertNull($e->getRuleName());
        }
    }

    public function testFailuresThatDoNotReproduceHaveNoLocation(): void
    {
        $calls = 0;
        $flaky = new CallbackProposition(static function () use (&$calls): bool {
            if (1 === ++$calls) {
                throw new UndefinedFactException('first call only');
            }

            return true;
        });

        try {
            new Rule($flaky, name: 'flaky')->evaluate(new Context());
            self::fail('Expected an exception.');
        } catch (EvaluationException $e) {
            self::assertSame('Rule "flaky" failed: first call only', $e->getMessage());
            self::assertSame('', $e->getFailurePath());
        }
    }

    public function testIncompleteOperatorsAreReportedWithoutOperands(): void
    {
        $explanation = new Explainer()->explain(new EqualTo(new Variable(null, 1)), new Context());

        self::assertTrue($explanation->failed());
        self::assertSame([], $explanation->children);
        self::assertStringContainsString('EqualTo takes exactly 2 operands', (string) $explanation);
    }

    public function testCustomPropositionsAreNamedByTheRegistryWhenGiven(): void
    {
        $rule = new Rule(new TrueProposition());
        $registry = new OperatorRegistry();
        $registry->register('always', TrueProposition::class);

        self::assertSame(TrueProposition::class, $rule->explain(new Context())->children[0]->name);
        self::assertSame('always', $rule->explain(new Context(), $registry)->children[0]->name);
    }

    public function testInvalidUtf8DoesNotBreakReporting(): void
    {
        $explanation = new Explanation('', 'value', null, "caf\xE9");

        self::assertSame("value: \"caf\u{FFFD}\"", (string) $explanation);
    }
}
