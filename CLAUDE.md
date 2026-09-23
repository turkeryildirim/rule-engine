# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`turkeryildirim/rule-engine` (https://github.com/turkeryildirim/rule-engine) is a stateless production rules engine library for PHP 8.5+ (no runtime dependencies), a rewrite of bobthecow/Ruler. The namespace is still `Ruler\`. There is no application to run; the test suite is the main way to exercise the code.

## Commands

```bash
composer install --prefer-source                   # vendor/ and composer.lock are not committed
composer check                                     # everything CI runs: cs, stan, test
composer test                                      # PHPUnit 13, random order
vendor/bin/phpunit tests/Operator/AdditionTest.php # one test file
vendor/bin/phpunit --filter testAddition           # one test method / pattern
composer stan                                      # PHPStan, level max + strict-rules, src and tests
composer cs        / composer cs-fix               # PHP-CS-Fixer check / apply
```

On this machine `api.github.com` is unreachable, so plain `composer install/update` fails on dist downloads. Use `--prefer-source` (github.com works).

`phpunit.xml.dist` runs in random order and fails on any PHP deprecation, notice, warning or risky test. Test metadata uses attributes (`#[DataProvider]`, `#[Test]`), and data providers must be `public static`.

Every file has `declare(strict_types=1)`. Style (`.php-cs-fixer.dist.php`): Symfony preset, aligned `=>`, **fully qualified native function calls** (`\count()`, `\is_numeric(...)`), `self::assert*` in tests. Methods that override or implement a parent/interface method carry `#[\Override]` (enforced by PHPStan's `checkMissingOverrideMethodAttribute`).

PHPStan must stay at zero errors without a baseline. The few `@phpstan-ignore` comments are deliberate and carry a reason; don't add new ones to silence real findings.

Do not enable PHP-CS-Fixer's `strict_comparison`. An automated `==`→`===` sweep is what silently broke `equalTo` and the division-by-zero guards upstream (commit bbe82cf). Likewise, don't "modernize" `Value::startsWith/endsWith` to `str_starts_with`/`str_ends_with`: they would lose the case-insensitive flag of `substr_compare()`.

## Architecture

Everything evaluates against a `Context` in two roles, split by interface:

- **`Proposition`** (`evaluate(Context): bool`) — things that are true or false. `Rule` is itself a Proposition (a condition + optional action), so rules nest inside logical operators. `Rule::execute()` calls the action with the Context.
- **`VariableOperand`** (`prepareValue(Context): Value`) — things that produce a value. `Variable` resolves by name from the Context, falling back to its default value (which may itself be a VariableOperand).

`Value` (immutable, `readonly`) holds the comparison/arithmetic/string logic and `Set extends Value` holds set logic. Operators are thin wrappers that pull operands, call `prepareValue()`, and delegate to `Value`/`Set`.

Semantics that are deliberate (chosen by the project owner) and covered by tests:
- `equalTo` is **strict** (`===`), `sameAs` is **loose** (`==`). This is the reverse of PHPUnit's naming.
- `Set` membership is **type-sensitive**. Every member gets an identity key from `Set::keyOf()`: `serialize()` for scalars, `spl_object_id` for objects, sorted member keys for nested Sets (so they compare order-independently). All set operations are keyed-array operations on those keys. Never go back to `array_unique`/`array_diff`, which compare by string cast.
- `modulo` uses `%` for two ints and `fmod()` otherwise. Any zero divisor (`0`, `0.0`, `"0"`) throws `RuntimeException('Division by zero')`.
- String operators treat `null` as matching nothing, convert int/float/Stringable, and throw `RuntimeException` for anything else.

### Operators (`src/Operator/`)

Each operator is one class. `Operator<TOperand>` stores operands and validates their count with the `Cardinality` enum (`Unary`/`Binary`/`Multiple`) returned from `getOperandCardinality(): Cardinality`. Custom operators must implement that too. Too many operands throws on add (`pushOperand()`); too few throws from `getOperands()`. Tests build logical operators incrementally with `addProposition()`, so the lower bound can't be checked eagerly.

- `VariableOperator` (`@extends Operator<VariableOperand>`): a subclass implements either `Proposition` (comparisons) or `VariableOperand` (math/set ops returning a new `Value`).
- `PropositionOperator` → `LogicalOperator`: operands are Propositions. `LogicalOperator` takes an **array** of propositions in its constructor, unlike other operators, which take variadic operands.

### RuleBuilder DSL (`src/RuleBuilder*`)

`RuleBuilder` is `ArrayAccess`: `$rb['name']` returns a cached `RuleBuilder\Variable`, and `$rb['user']['roles']` returns a `RuleBuilder\VariableProperty`. Names must be strings.

Both `VariableProperty` classes delegate to `PropertyResolver::resolve()`. The lookup order is: public method (`method_exists` && `is_callable`, so private methods and `__call` are skipped — `is_callable` alone is true for any name on objects with `__call`), then a property that is set (magic `__isset`/`__get` included), then an `ArrayAccess` offset, then an array key, then the default.

Fluent methods on `RuleBuilder\Variable` are **hardcoded per built-in operator**. Arguments go through `asVariable()`/`asVariables()`. Value-producing operators are wrapped via `wrap(VariableOperand)` so chaining continues.

Unknown method calls go through `__call`. It resolves `ucfirst($name)` in namespaces registered with `registerOperatorNamespace()`, and accepts only classes implementing `Proposition` or `VariableOperand` (see `tests/Fixtures/ALotGreaterThan.php`, `PlusOne.php`).

**Adding a built-in operator** touches:
- a new class in `src/Operator/`,
- usually a method on `Value`/`Set`,
- a fluent method in `src/RuleBuilder/Variable.php`,
- a `tests/Operator/*Test.php`,
- the README operator tables.

### Context

`Context` is a Pimple-style container. Closures and invokable objects are invoked lazily with the Context on every read. `share()` resolves once and then freezes the fact. `protect()` stores a callable as a literal value. Fact names must be strings or ints.

## Tests

Tests live in `tests/` under `Ruler\Test\`, mirror `src/`, and must end in `Test.php`. `tests/Functional/` has end-to-end DSL examples. Test doubles live in `tests/Fixtures/`. Use `CallCounter` rather than by-reference closure variables, which PHPStan can't track. Behavior changes and bug fixes need tests.
