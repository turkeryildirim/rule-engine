<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\SerializationException;
use D6N\RuleEngine\MatchMode;
use D6N\RuleEngine\OperatorRegistry;
use D6N\RuleEngine\Rule;
use D6N\RuleEngine\RuleBuilder;
use D6N\RuleEngine\RuleOrder;
use D6N\RuleEngine\RuleSerializer;
use D6N\RuleEngine\RuleSet;
use D6N\RuleEngine\Test\Fixtures\CallCounter;
use D6N\RuleEngine\Test\Fixtures\TrueProposition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RuleSerializerTest extends TestCase
{
    public function testExportsTheDocumentedFormat(): void
    {
        $rb = new RuleBuilder();
        $rule = $rb->create(
            $rb->logicalAnd(
                $rb['orderTotal']->greaterThanOrEqualTo(50),
                $rb['user']['country']->in(['TR', 'DE']),
            ),
            name: 'freeShipping',
        );

        self::assertSame(<<<'JSON'
            {
                "version": 1,
                "rule": {
                    "name": "freeShipping",
                    "condition": {
                        "op": "logicalAnd",
                        "operands": [
                            {
                                "op": "greaterThanOrEqualTo",
                                "operands": [
                                    {
                                        "var": "orderTotal"
                                    },
                                    {
                                        "value": 50
                                    }
                                ]
                            },
                            {
                                "op": "in",
                                "operands": [
                                    {
                                        "property": "country",
                                        "of": {
                                            "var": "user"
                                        }
                                    },
                                    {
                                        "value": [
                                            "TR",
                                            "DE"
                                        ]
                                    }
                                ]
                            }
                        ]
                    }
                }
            }
            JSON, new RuleSerializer()->toJson($rule));
    }

    public function testRuleSetsSurviveARoundTrip(): void
    {
        $registry = new OperatorRegistry();
        $registry->registerNamespace('D6N\RuleEngine\Test\Fixtures');
        $registry->register('always', TrueProposition::class);
        $rb = new RuleBuilder($registry);
        $rb['user']->offsetSet('tier', 'basic');
        $vip = $rb->create($rb['user']['tier']->equalTo('vip'), name: 'vip');

        $set = new RuleSet();
        $set->addRule($rb->create($rb->logicalOr($vip, $rb['total']->multiply(1.0)->round(0)->greaterThan(100)), name: 'big'), 10);
        $set->addRule($rb->create($rb->atLeast(2, $rb['tags']->union(['sale'])->setContains('sale'), $rb['total']->aLotGreaterThan(5), new TrueProposition()), name: 'promo')); // @phpstan-ignore method.notFound, argument.type (custom operator via __call)
        $set->addRule($rb->create($rb['city']->stringContainsInsensitive('İstanbul')), -5);

        $serializer = new RuleSerializer($registry);
        $json = $serializer->toJson($set);
        $actions = ['big' => new CallCounter(), 'promo' => new CallCounter()];
        $imported = $serializer->ruleSetFromJson($json, $actions);

        self::assertSame($json, $serializer->toJson($imported));
        self::assertStringContainsString('1.0', $json, 'floats keep their type');

        foreach ([
            ['user' => ['tier' => 'vip'], 'total' => 1, 'tags' => [], 'city' => 'Ankara'],
            ['user' => [], 'total' => 200, 'tags' => ['new'], 'city' => 'ISTANBUL'],
            ['user' => null, 'total' => 3, 'tags' => null, 'city' => 'İzmir'],
        ] as $facts) {
            $context = new Context($facts);
            self::assertSame(
                \array_map(static fn (Rule $rule): ?string => $rule->getName(), $set->evaluateRules($context, MatchMode::All, RuleOrder::Priority)),
                \array_map(static fn (Rule $rule): ?string => $rule->getName(), $imported->evaluateRules($context, MatchMode::All, RuleOrder::Priority)),
            );
        }

        $imported->executeRules(new Context(['user' => ['tier' => 'vip'], 'total' => 200, 'tags' => [], 'city' => 'x']));
        self::assertSame(1, $actions['big']->calls);
        self::assertSame(1, $actions['promo']->calls);
    }

    public function testSingleRulesSurviveARoundTripAsArrays(): void
    {
        $rb = new RuleBuilder();
        $rule = $rb->create($rb['birthDate']->olderThan('18 years'), new CallCounter(), 'adult');
        $serializer = new RuleSerializer();

        $array = $serializer->toArray($rule);
        $imported = $serializer->ruleFromArray($array);

        self::assertSame($array, $serializer->toArray($imported));
        self::assertSame('adult', $imported->getName());
        self::assertFalse($imported->hasAction(), 'actions are not stored');
        self::assertSame($serializer->toJson($rule, pretty: false), $serializer->toJson($imported, pretty: false));
        self::assertStringNotContainsString("\n", $serializer->toJson($rule, pretty: false));
    }

    public function testDefaultsAreKept(): void
    {
        $rb = new RuleBuilder();
        $rb->offsetSet('user', ['country' => 'TR']);
        $rb['user']->offsetSet('roles', ['guest']);
        $serializer = new RuleSerializer();

        $imported = $serializer->ruleFromJson($serializer->toJson($rb->create($rb['user']['roles']->setContains('guest'))));

        self::assertTrue($imported->evaluate(new Context()));
    }

    /**
     * @param \Closure(RuleBuilder): Rule $build
     */
    #[DataProvider('unexportable')]
    public function testRefusesToExportWhatItCannotImport(\Closure $build, string $message): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage($message);

        new RuleSerializer()->toJson($build(new RuleBuilder()));
    }

    /**
     * @return iterable<string, array{\Closure(RuleBuilder): Rule, string}>
     */
    public static function unexportable(): iterable
    {
        yield 'object literal' => [
            static fn (RuleBuilder $rb): Rule => $rb->create($rb['d']->after(new \DateTimeImmutable('2026-01-01'))),
            'Cannot export a DateTimeImmutable value; only null, scalars, finite floats and arrays can be stored (at rule.condition.operands[1].value)',
        ];
        yield 'nested object literal' => [
            static fn (RuleBuilder $rb): Rule => $rb->create($rb['d']->in([1, [new \stdClass()]])),
            'Cannot export a stdClass value; only null, scalars, finite floats and arrays can be stored (at rule.condition.operands[1].value[1][0])',
        ];
        yield 'non-finite float' => [
            static fn (RuleBuilder $rb): Rule => $rb->create($rb['n']->lessThan(\INF)),
            'Cannot export a non-finite float',
        ];
        yield 'unregistered proposition' => [
            static fn (RuleBuilder $rb): Rule => $rb->create($rb->logicalNot(new TrueProposition())),
            'is not registered; register it or its namespace first. (at rule.condition.operands[0])',
        ];
    }

    #[DataProvider('invalidDocuments')]
    public function testRejectsInvalidDocumentsWithTheirPath(string $json, string $message): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage($message);

        new RuleSerializer()->ruleSetFromJson($json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDocuments(): iterable
    {
        $rules = static fn (string $rules): string => '{"version": 1, "rules": '.$rules.'}';
        $condition = static fn (string $node): string => $rules('[{"condition": '.$node.'}]');

        yield 'not JSON' => ['{', 'Invalid JSON: Syntax error'];
        yield 'not an object' => ['"rule"', 'The document must be a JSON object'];
        yield 'unknown version' => ['{"version": 2, "rules": []}', 'Unsupported document version; expected 1 (at version)'];
        yield 'missing rules' => ['{"version": 1}', 'Missing "rules"'];
        yield 'rules not a list' => [$rules('{"a": 1}'), '"rules" must be a list (at rules)'];
        yield 'rule not an object' => [$rules('[1]'), 'A rule must be an object (at rules[0])'];
        yield 'name not a string' => [$rules('[{"name": 1, "condition": {}}]'), '"name" must be a string (at rules[0].name)'];
        yield 'priority not an int' => [$rules('[{"priority": "high", "condition": {"op": "logicalAnd", "operands": [{"rule": {"condition": {"op": "isNull", "operands": [{"value": 1}]}}}]}}]'), '"priority" must be an integer (at rules[0].priority)'];
        yield 'missing condition' => [$rules('[{}]'), 'Missing "condition" (at rules[0])'];
        yield 'condition is a value' => [$condition('{"value": true}'), 'A condition must be a proposition, not a value (at rules[0].condition)'];
        yield 'node not an object' => [$condition('[1]'), 'Unknown node; expected one of "op", "var", "property", "value" or "rule" (at rules[0].condition)'];
        yield 'scalar node' => [$condition('"x"'), 'A node must be an object (at rules[0].condition)'];
        yield 'unknown operator' => [$condition('{"op": "nope"}'), 'Unknown operator: "nope" (at rules[0].condition.op)'];
        yield 'operator not string' => [$condition('{"op": 1}'), '"op" must be a string (at rules[0].condition.op)'];
        yield 'operands not a list' => [$condition('{"op": "logicalAnd", "operands": {"a": 1}}'), '"operands" must be a list (at rules[0].condition.operands)'];
        yield 'bad operand' => [$condition('{"op": "logicalNot", "operands": [2]}'), 'A node must be an object (at rules[0].condition.operands[0])'];
        yield 'var not a string' => [$condition('{"op": "isNull", "operands": [{"var": 1}]}'), '"var" must be a string (at rules[0].condition.operands[0].var)'];
        yield 'property of a value' => [$condition('{"op": "isNull", "operands": [{"property": "a", "of": {"value": 1}}]}'), 'A property can only be read from a "var" or "property" node (at rules[0].condition.operands[0].of)'];
        yield 'count not an int' => [$condition('{"op": "atLeast", "count": "2", "operands": []}'), '"count" must be an integer (at rules[0].condition.count)'];
        yield 'wrong operand type' => [$condition('{"op": "logicalNot", "operands": [{"value": 1}]}'), 'Invalid operands for "logicalNot"'];
        yield 'wrong operand count' => [$condition('{"op": "equalTo", "operands": [{"value": 1}, {"value": 1}, {"value": 1}]}'), 'Invalid operands for "equalTo": D6N\RuleEngine\Operator\EqualTo takes exactly 2 operands (at rules[0].condition)'];
    }

    public function testSingleRuleDocumentsNeedARule(): void
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage('Missing "rule"');

        new RuleSerializer()->ruleFromJson('{"version": 1}');
    }

    public function testExceptionExposesThePath(): void
    {
        try {
            new RuleSerializer()->ruleFromJson('{"version": 1, "rule": {"condition": {"op": "nope"}}}');
            self::fail('Expected an exception.');
        } catch (SerializationException $e) {
            self::assertSame('rule.condition.op', $e->getPath());
            self::assertSame('', new SerializationException('x')->getPath());
        }
    }

    public function testNestedPropertiesSurviveARoundTrip(): void
    {
        $rb = new RuleBuilder();
        $serializer = new RuleSerializer();
        $rule = $rb->create($rb['order']['customer']['address']['city']->equalTo('Ankara'));

        $imported = $serializer->ruleFromJson($serializer->toJson($rule));

        self::assertSame($serializer->toJson($rule), $serializer->toJson($imported));
        self::assertTrue($imported->evaluate(new Context(['order' => ['customer' => ['address' => ['city' => 'Ankara']]]])));
    }

    public function testInvalidUtf8IsSubstitutedOnExport(): void
    {
        $rb = new RuleBuilder();

        $json = new RuleSerializer()->toJson($rb->create($rb['name']->equalTo("caf\xE9")), pretty: false);

        self::assertStringContainsString("caf\u{FFFD}", $json);
    }

    public function testActionsAreAttachedToNestedRulesToo(): void
    {
        $rb = new RuleBuilder();
        $serializer = new RuleSerializer();
        $json = $serializer->toJson($rb->create($rb->logicalNot($rb->create($rb['a']->isNull(), name: 'inner')), name: 'outer'));

        $imported = $serializer->ruleFromJson($json, ['inner' => new CallCounter()]);
        $not = $imported->getCondition();
        self::assertInstanceOf(\D6N\RuleEngine\Operator\LogicalNot::class, $not);
        $inner = $not->getOperands()[0];

        self::assertInstanceOf(Rule::class, $inner);
        self::assertTrue($inner->hasAction());
        self::assertFalse($imported->hasAction());
    }

    public function testSingleRulesAndNestedRulesMustBeObjects(): void
    {
        foreach (['{"version": 1, "rule": 1}', '{"version": 1, "rule": {"condition": {"op": "logicalNot", "operands": [{"rule": 1}]}}}'] as $json) {
            try {
                new RuleSerializer()->ruleFromJson($json);
                self::fail('Expected an exception for '.$json);
            } catch (SerializationException $e) {
                self::assertStringStartsWith('A rule must be an object', $e->getMessage());
            }
        }
    }
}
