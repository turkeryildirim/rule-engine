# Rule Engine

[![Tests](https://github.com/turkeryildirim/rule-engine/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/turkeryildirim/rule-engine/actions/workflows/php.yml)

A small, stateless production rules engine for PHP 8.5+.

You describe conditions with a fluent DSL, feed them facts through a `Context`, and either ask whether a rule holds or let it run an action. Rules are plain objects: they don't care where your data comes from, and they don't store anything between evaluations.

```php
use D6N\RuleEngine\Context;
use D6N\RuleEngine\RuleBuilder;

$rb = new RuleBuilder();

$freeShipping = $rb->create(
    $rb->logicalAnd(
        $rb['orderTotal']->greaterThanOrEqualTo(50),
        $rb['country']->equalTo('TR'),
    ),
    static function (Context $context): void {
        echo "Free shipping for order {$context['orderId']}\n";
    },
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

Requires PHP 8.5 or newer and has no runtime dependencies. Classes live in the `D6N\RuleEngine\` namespace.

## Core concepts

| Concept | What it is |
|---|---|
| `Context` | The facts a rule is evaluated against. Values can be plain values or closures that are resolved lazily. |
| `Variable` | A named placeholder, such as `$rb['orderTotal']`. It is replaced by the matching fact from the Context, or falls back to its default value. |
| Proposition | Anything that evaluates to `true` or `false`: comparisons, logical operators and rules themselves. |
| `Rule` | A proposition plus an optional action. Because a rule is also a proposition, rules can be nested inside other rules. |
| `RuleSet` | A collection of rules that are executed together. |

## Building conditions

Everything below is available on a RuleBuilder variable (`$rb['name']`). Arguments can be other variables or plain values.

### Comparison

| Method | True when |
|---|---|
| `equalTo($b)` / `notEqualTo($b)` | `$a === $b` / `$a !== $b` (strict) |
| `sameAs($b)` / `notSameAs($b)` | `$a == $b` / `$a != $b` (loose, PHP 8 rules) |
| `greaterThan($b)` / `greaterThanOrEqualTo($b)` | `$a > $b` / `$a >= $b` |
| `lessThan($b)` / `lessThanOrEqualTo($b)` | `$a < $b` / `$a <= $b` |

> **Note:** unlike PHPUnit's `assertSame()`, `equalTo` is the **strict** comparison and `sameAs` is the **loose** one. So `$rb['a']->equalTo('1')` is false when `a` is the integer `1`, while `sameAs('1')` is true.

### Strings

| Method | True when |
|---|---|
| `stringContains($b)` / `stringDoesNotContain($b)` | `$a` contains `$b` |
| `stringContainsInsensitive($b)` / `stringDoesNotContainInsensitive($b)` | the same, ignoring ASCII case |
| `startsWith($b)` / `startsWithInsensitive($b)` | `$a` starts with `$b` |
| `endsWith($b)` / `endsWithInsensitive($b)` | `$a` ends with `$b` |

Integers, floats and `Stringable` objects are treated as strings. A `null` value never contains, starts with or ends with anything, and an empty prefix or suffix never matches. Arrays and other non-string values throw a `RuntimeException`.

### Math

Math operators produce a new value that you can keep chaining:

```php
$rb['price']->add($rb['shipping'])->multiply(1.2)->greaterThan(100);
```

Available operators: `add`, `subtract`, `multiply`, `divide`, `modulo`, `exponentiate`, `negate`, `ceil`, `floor`.

Operands must be numbers or numeric strings, otherwise a `RuntimeException` is thrown. Dividing by any form of zero (`0`, `0.0`, `"0"`) or raising zero to a negative power throws `RuntimeException('Division by zero')`. `modulo` uses `%` for integers and `fmod()` for anything else, so `5.5 % 2` is `1.5`.

### Sets

Any value can be treated as a set: arrays become a set of their values (keys are discarded), `null` becomes the empty set, and anything else becomes a single-member set.

```php
$rb['tags']->union(['sale', 'new']);
$rb['tags']->intersect($rb['allowedTags']);
$rb['tags']->complement(['draft']);
$rb['tags']->symmetricDifference($rb['otherTags']);
$rb['scores']->min();
$rb['scores']->max();

$rb['roles']->setContains('admin');
$rb['roles']->setDoesNotContain('banned');
$rb['roles']->containsSubset(['editor', 'author']);
$rb['roles']->doesNotContainSubset(['owner']);
```

Set membership is **type-sensitive**: `1`, `"1"`, `1.0` and `true` are four different members. Nested arrays are compared by content regardless of order. Objects are compared by identity.

### Logic

```php
$rb->logicalAnd($p, $q);
$rb->logicalOr($p, $q);
$rb->logicalXor($p, $q); // exactly one is true
$rb->logicalNot($p);
```

`logicalAnd`, `logicalOr` and `logicalXor` accept any number of propositions.

## Evaluating and executing

```php
$isAdult = $rb->create($rb['age']->greaterThanOrEqualTo(18));

if ($isAdult->evaluate($context)) {
    // ...
}
```

When you `execute()` a rule and its condition holds, its action is called with the `Context`. An action that is not callable is rejected with a `TypeError` as soon as the rule is created.

```php
use D6N\RuleEngine\RuleSet;

$rules = new RuleSet([$welcomeBack, $askToSignUp]);
$rules->addRule($redirectToLogin);

$rules->executeRules($context); // runs the action of every rule whose condition holds
```

Adding the same rule to a `RuleSet` more than once has no effect.

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

Reading a fact that was never defined throws an `InvalidArgumentException`. A shared fact cannot be redefined once it has been resolved.

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

Register the namespace, then call the operator by its class name in camelCase:

```php
$rb->registerOperatorNamespace('App\Rules');

$rb->create($rb['a']->aLotGreaterThan(10));
```

`Cardinality` is `Unary`, `Binary` or `Multiple` (one or more operands). Adding too many operands throws a `LogicException` immediately; too few is reported when the operator is evaluated. Only classes that implement `Proposition` or `VariableOperand` are picked up from a registered namespace.

## Development

```bash
composer install --prefer-source
composer check      # everything CI runs: cs, stan, test
composer test       # PHPUnit 13, random order, fails on any deprecation
composer stan       # PHPStan at max level with strict rules
composer cs-fix     # apply the coding standard
```

## Credits and license

This project is a PHP 8.5 rewrite of [Ruler](https://github.com/bobthecow/Ruler) by Justin Hileman and contributors. Released under the MIT license; see [LICENSE](LICENSE).
