# Rule Engine

[![Tests](https://github.com/turkeryildirim/rule-engine/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/turkeryildirim/rule-engine/actions/workflows/php.yml)

A small, stateless production rules engine for PHP 8.5+.

You describe conditions with a fluent DSL, feed them facts through a `Context`, and either ask whether a rule holds or let it run an action. Rules can be stored as JSON, run in sets with different match strategies, and explained node by node when you need to know why they did (or didn't) match.

```php
use D6N\RuleEngine\Context;
use D6N\RuleEngine\RuleBuilder;

$rb = new RuleBuilder();

$freeShipping = $rb->create(
    $rb->logicalAnd(
        $rb['orderTotal']->greaterThanOrEqualTo(50),
        $rb['country']->in(['TR', 'DE']),
    ),
    static function (Context $context): void {
        echo "Free shipping for order {$context['orderId']}\n";
    },
    name: 'freeShipping',
);

$context = new Context([
    'orderId'    => 1042,
    'orderTotal' => 72.5,
    'country'    => 'TR',
]);

$freeShipping->evaluate($context); // true
$freeShipping->execute($context);  // prints "Free shipping for order 1042"
```

## Installation

```bash
composer require turkeryildirim/rule-engine
```

Requires PHP 8.5+ with `ext-mbstring`; no other dependencies. Classes live in the `D6N\RuleEngine\` namespace.

## Core concepts

| Concept | What it is |
|---|---|
| `Context` | The facts a rule is evaluated against. Values can be plain values or closures that are resolved lazily. |
| `Variable` | A named placeholder, such as `$rb['orderTotal']`. It is replaced by the matching fact from the Context, or falls back to its default value. |
| Proposition | Anything that evaluates to `true` or `false`: comparisons, logical operators and rules themselves. |
| `Rule` | A proposition plus an optional action and name. Because a rule is also a proposition, rules can be nested inside other rules. |
| `RuleSet` | A collection of rules, executed with a match mode and order. |

## Operators

Everything below is called on a RuleBuilder variable (`$rb['name']`). Arguments can be other variables or plain values. Operators that answer true/false are propositions; operators that produce a value can be chained (`$rb['items']->sum()->multiply(1.2)->round(2)->greaterThan(100)`).

### Comparison

| Method | True when |
|---|---|
| `equalTo($b)` / `notEqualTo($b)` | `$a === $b` / `$a !== $b` (strict) |
| `sameAs($b)` / `notSameAs($b)` | `$a == $b` / `$a != $b` (loose, PHP 8 rules) |
| `greaterThan($b)` / `greaterThanOrEqualTo($b)` | `$a > $b` / `$a >= $b` |
| `lessThan($b)` / `lessThanOrEqualTo($b)` | `$a < $b` / `$a <= $b` |
| `between($min, $max)` | `$min <= $a <= $max` |
| `in($list)` / `notIn($list)` | `$a` is (not) one of the list's items (type-sensitive) |

> **Note:** unlike PHPUnit's `assertSame()`, `equalTo` is the **strict** comparison and `sameAs` is the **loose** one. So `$rb['a']->equalTo('1')` is false when `a` is the integer `1`, while `sameAs('1')` is true.

### Strings

| Method | True when / result |
|---|---|
| `stringContains($b)` / `stringDoesNotContain($b)` | `$a` contains `$b` |
| `startsWith($b)` / `endsWith($b)` | `$a` starts / ends with `$b` |
| `stringContainsInsensitive`, `stringDoesNotContainInsensitive`, `startsWithInsensitive`, `endsWithInsensitive` | the same, ignoring case |
| `containsAny($list)` / `containsAll($list)` | `$a` contains at least one / every string in the list |
| `startsWithAny($list)` / `endsWithAny($list)` | `$a` starts / ends with one of the strings |
| `matches($pattern)` | `$a` matches the PCRE pattern, e.g. `'/^[A-Z]{2}\d+$/'` |
| `length()` | the number of characters (UTF-8), or of items in an array |

Integers, floats and `Stringable` objects are treated as strings. A `null` value never contains, starts with, ends with or matches anything, and an empty prefix or suffix never matches. Arrays and other non-string values throw an `InvalidOperandException`, as does an invalid regular expression.

The case-insensitive operators use Unicode case folding (`ÇAĞRI` = `çağrı`, `Straße` = `STRASSE`) and additionally treat the Turkish dotted and dotless i (`İ`, `I`, `ı`, `i`) as the same letter, so `İSTANBUL` = `istanbul`. The price is that words that differ only in that letter (such as *kır* and *kir*) also match each other.

### Math

| Method | Result |
|---|---|
| `add`, `subtract`, `multiply`, `divide`, `modulo`, `exponentiate` | `$a` combined with the argument |
| `negate()`, `abs()`, `ceil()`, `floor()` | `-$a`, `abs($a)`, … |
| `round($precision)` | rounded half away from zero; precision `0` or less gives an int |
| `sum()`, `avg()`, `count()` | over an array (duplicates included); `avg()` of an empty array is `null` |

Operands must be numbers or numeric strings, otherwise an `ArithmeticException` is thrown. Dividing by any form of zero (`0`, `0.0`, `"0"`) or raising zero to a negative power throws `DivisionByZeroException`. `modulo` uses `%` for integers and `fmod()` for anything else, so `5.5 % 2` is `1.5`.

### Sets

| Method | Result |
|---|---|
| `union(...$sets)`, `intersect(...$sets)`, `complement(...$sets)`, `symmetricDifference($set)` | a new set |
| `min()`, `max()` | the smallest / largest number in the set |
| `setContains($b)` / `setDoesNotContain($b)` | `$b` is (not) a member |
| `containsSubset($set)` / `doesNotContainSubset($set)` | every member of `$set` is (not all) in `$a` |

Arrays become a set of their values (keys are discarded), `null` becomes the empty set, and anything else becomes a single-member set. Membership is **type-sensitive**: `1`, `"1"`, `1.0` and `true` are four different members. Nested arrays are compared by content regardless of order. Objects are compared by identity.

### Types

`isNull()`, `isEmpty()`, `isString()`, `isNumeric()`, `isArray()`, `isBool()`. `isEmpty()` is true for `null`, `""`, `[]` and empty `Countable`s; unlike PHP's `empty()`, `0`, `"0"` and `false` are **not** empty.

### Dates

| Method | True when |
|---|---|
| `before($date)` / `after($date)` | strictly before / after |
| `betweenDates($start, $end)` | `$start <= $a <= $end` |
| `withinLast($interval)` | `$a` is in the given period before now, e.g. `withinLast('7 days')` |
| `olderThan($interval)` | `$a` is at least that long ago, e.g. `olderThan('18 years')` |

Dates can be `DateTimeInterface` objects, Unix timestamps or date strings; intervals can be `DateInterval` objects or strings such as `'7 days'`. "Now" comes from the Context's clock, so rules are testable:

```php
use D6N\RuleEngine\Clock;

final class FixedClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-09-23 12:00 UTC');
    }
}

$context = new Context(['birthDate' => '2008-09-23'], new FixedClock());
$rb['birthDate']->olderThan('18 years')->evaluate($context); // true
```

Without a clock the Context uses the system time.

### Logic

These are called on the RuleBuilder itself:

| Method | True when |
|---|---|
| `logicalAnd($p, $q, ...)` / `logicalOr(...)` | all / any hold |
| `logicalNot($p)` | `$p` does not hold |
| `logicalXor($p, $q, ...)` | exactly one holds |
| `logicalNand(...)` / `logicalNor(...)` | not all / none hold |
| `logicalImplies($if, $then)` | `$if` does not hold, or `$then` holds |
| `atLeast($n, ...)` / `atMost($n, ...)` / `exactly($n, ...)` | at least / at most / exactly `$n` hold |

## Rules and rule sets

```php
$isAdult = $rb->create($rb['age']->greaterThanOrEqualTo(18), name: 'adult');

$isAdult->evaluate($context); // true or false
$isAdult->execute($context);  // runs the action if the condition holds; returns whether it held
```

The action receives the `Context`. An action that is not callable is rejected with a `TypeError` when the rule is created.

A `RuleSet` runs several rules. Each rule can have a priority, and `executeRules()` / `evaluateRules()` take a match mode and an order:

```php
use D6N\RuleEngine\MatchMode;
use D6N\RuleEngine\RuleOrder;
use D6N\RuleEngine\RuleSet;

$rules = new RuleSet();
$rules->addRule($goldDiscount, priority: 20);
$rules->addRule($silverDiscount, priority: 10);
$rules->addRule($welcomeDiscount);

$rules->executeRules($context);                                        // every match, in insertion order
$rules->executeRules($context, MatchMode::All, RuleOrder::Priority);   // every match, highest priority first
$rules->executeRules($context, MatchMode::First);                      // only the first match
$rules->executeRules($context, MatchMode::First, RuleOrder::Priority); // only the highest-priority match
$rules->executeRules($context, MatchMode::Last);                       // only the last match
$rules->executeRules($context, MatchMode::Last, RuleOrder::Priority);  // only the lowest-priority match
```

Both methods return the matched rules; `evaluateRules()` does not run any action. With `First` and `Last`, rules after the match are not evaluated. Equal priorities keep their insertion order, and adding a rule that is already in the set only updates its priority.

## Storing rules as JSON

```php
use D6N\RuleEngine\RuleSerializer;

$serializer = new RuleSerializer();

$json = $serializer->toJson($rules);    // a Rule or a RuleSet
$rules = $serializer->ruleSetFromJson($json, [
    'goldDiscount'   => $applyGoldDiscount,   // actions are attached by rule name
    'silverDiscount' => $applySilverDiscount,
]);
```

The document looks like this:

```json
{
    "version": 1,
    "rules": [
        {
            "name": "freeShipping",
            "priority": 10,
            "condition": {
                "op": "logicalAnd",
                "operands": [
                    {"op": "greaterThanOrEqualTo", "operands": [{"var": "orderTotal"}, {"value": 50}]},
                    {"op": "in", "operands": [{"property": "country", "of": {"var": "user"}}, {"value": ["TR", "DE"]}]}
                ]
            }
        }
    ]
}
```

`toArray()`, `ruleFromArray()` and `ruleSetFromArray()` do the same with plain arrays. Actions are closures and cannot be stored, so they are passed in on import. Literal values must be `null`, scalars, finite floats or arrays of those; exporting anything else throws a `SerializationException`, and so does importing an invalid document. The exception message names the offending node, e.g. `(at rules[0].condition.operands[1])`. Floats stay floats (`1.0` is written as `1.0`), so type-sensitive comparisons survive the round trip.

## Explaining and debugging rules

`explain()` evaluates every node of a rule and shows what each one produced:

```php
echo $freeShipping->explain(new Context(['orderTotal' => 20, 'country' => 'TR']));
```

```text
rule freeShipping: false
  logicalAnd: false
    greaterThanOrEqualTo: false
      var orderTotal: 20
      value: 50
    in: true
      var country: "TR"
      value: ["TR","DE"]
```

The explanation is also available as an array or JSON (`toArray()`, `json_encode()`), with each node's `path`, `type`, `name`, `result` and `error`. Explaining evaluates every operand, even where normal evaluation would short-circuit.

When a condition fails with an error, `Rule::evaluate()` (and so `execute()` and `RuleSet`) throws an `EvaluationException` that says where it happened:

```text
Rule "average" failed at condition.operands[1].operands[0] (logicalAnd > greaterThan > divide): Division by zero
```

`getPrevious()` returns the original exception, `getRuleName()` and `getFailurePath()` the location, and `getExplanation()` the full tree with every value that led up to the error. Paths use the same notation as the JSON format.

## Exceptions

Every exception implements `D6N\RuleEngine\Exception\RuleEngineException` and also extends the SPL exception you would expect, so both specific and generic `catch` blocks work:

| Exception | Extends | Thrown when |
|---|---|---|
| `EvaluationException` | `RuntimeException` | a rule's condition fails; wraps the original error |
| `ArithmeticException`, `DivisionByZeroException` | `RuntimeException` | math on non-numbers, division by zero |
| `InvalidOperandException` | `RuntimeException` | a value of the wrong type, an invalid regex, date or interval |
| `UndefinedFactException` | `InvalidArgumentException` | reading a fact the Context does not define |
| `FrozenFactException` | `RuntimeException` | redefining a shared fact after it was resolved |
| `InvalidNameException`, `NotCallableException` | `InvalidArgumentException` | invalid fact/variable names, `share()`/`protect()` on a non-callable |
| `OperandCountException`, `UnknownOperatorException` | `LogicException` | wrong number of operands, unknown operator |
| `SerializationException` | `InvalidArgumentException` | JSON export or import problems |

## The Context

A `Context` is an `ArrayAccess` container of facts. Fact names must be strings or integers.

```php
$context = new Context();

// Plain values
$context['country'] = 'TR';

// Closures and invokable objects are resolved on every read, and receive the Context
$context['user'] = static fn (Context $c) => $users->find($c['userId']);

// share(): resolve once, then keep the result
$context['orderCount'] = $context->share(static fn (Context $c) => $orders->countFor($c['user']));

// protect(): store a closure as a value instead of calling it
$context['formatter'] = $context->protect(static fn (string $s) => \strtoupper($s));

$context->raw('user'); // the closure itself, without resolving it
$context->keys();      // ['country', 'user', 'orderCount', 'formatter']
```

## Reaching into values

Use a second pair of brackets to read a property of a fact:

```php
$rb['user']['roles']->setContains('admin');
```

If the fact is an object, the lookup tries, in order:
1. a public method named `roles` (`__call` is not used),
2. a public (or magic) property that is set,
3. an `ArrayAccess` offset.

If the fact is an array, it uses the `roles` key. When nothing matches, the property's default value is used, which you can set:

```php
$rb['user']['roles'] = ['anonymous'];
```

## Custom operators

Write a class that extends `VariableOperator` and implements either `Proposition` (it answers true or false) or `VariableOperand` (it produces a value):

```php
namespace App\Rules;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Operator\Cardinality;
use D6N\RuleEngine\Operator\VariableOperator;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\Value;

final class ALotGreaterThan extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$left, $right] = $this->getOperands();
        $tenTimesRight = new Value($right->prepareValue($context)->multiply(new Value(10)));

        return $left->prepareValue($context)->greaterThan($tenTimesRight);
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
```

Register it, then call it by name:

```php
use D6N\RuleEngine\OperatorRegistry;

$operators = new OperatorRegistry();
$operators->registerNamespace('App\Rules');          // any class in the namespace, by camelCase class name
$operators->register('muchBigger', ALotGreaterThan::class); // or one class under an explicit name

$rb = new RuleBuilder($operators);
$rb->create($rb['a']->aLotGreaterThan(10));
```

`Cardinality` is `Unary`, `Binary`, `Ternary` or `Multiple` (one or more operands). Adding too many operands throws an `OperandCountException` immediately; too few is reported when the operator is evaluated. Pass the same registry to `RuleSerializer` and `Rule::explain()` so they know your operators' names.

## Development

```bash
composer install
composer check      # everything CI runs: code style, static analysis, tests with coverage
composer test       # PHPUnit 13, random order, fails on any deprecation
composer coverage   # tests with coverage; fails below 100% line coverage
composer stan       # PHPStan at max level with strict rules
composer cs-fix     # apply the coding standard
```

`composer coverage` writes `build/coverage.json` (via [phpunit-json-coverage-report](https://github.com/turkeryildirim/phpunit-json-coverage-report)) and needs Xdebug or PCOV.

## Credits and license

This project is a rewrite of [Ruler](https://github.com/bobthecow/Ruler) by Justin Hileman and contributors. Released under the MIT license; see [LICENSE](LICENSE).
