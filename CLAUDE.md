# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`turkeryildirim/rule-engine` (https://github.com/turkeryildirim/rule-engine) is a stateless production rules engine library for PHP 8.5+ (requires `ext-mbstring` and `symfony/polyfill-intl-normalizer`). The namespace is `D6N\RuleEngine\` (tests: `D6N\RuleEngine\Test\`). There is no application to run; the test suite is the main way to exercise the code.

## Commands

```bash
composer install --prefer-source                   # vendor/ and composer.lock are not committed
composer check                                     # everything CI runs: cs, stan, coverage
composer test                                      # PHPUnit 13, random order, no coverage (fast)
composer coverage                                  # tests + build/coverage.json; fails below 100% lines
vendor/bin/phpunit tests/Operator/AdditionTest.php # one test file
vendor/bin/phpunit --filter testAddition           # one test method / pattern
composer stan                                      # PHPStan, level max + strict-rules, src, tests and bin
composer cs        / composer cs-fix               # PHP-CS-Fixer check / apply
```

On this machine `api.github.com` is unreachable, so plain `composer install/update` fails on dist downloads. Use `--prefer-source` (github.com works).

`phpunit.xml.dist` runs in random order and fails on any PHP deprecation, notice, warning or risky test. Test metadata uses attributes (`#[DataProvider]`, `#[Test]`), and data providers must be `public static`.

Coverage: the `turkeryildirim/phpunit-json-coverage-report` extension writes `build/coverage.json` whenever coverage is active. `bin/check-coverage.php` fails unless **line coverage is 100%** and lists the uncovered lines; branch coverage is also 100% (keep it there) and path coverage is reported only. Xdebug attributes calls made through first-class callables (`self::foo(...)`) to the wrong function, so use closures where branch coverage matters, and avoid `match` on enums whose "no arm matched" branch can never run (see `Cardinality`). Don't use `@codeCoverageIgnore`: delete unreachable code instead. `assert()` lines never count as executed, so don't use `assert()` in `src/`. Path coverage needs `memory_limit=2G`, which `composer coverage` sets.

Every file has `declare(strict_types=1)`. Style (`.php-cs-fixer.dist.php`): Symfony preset, aligned `=>`, **fully qualified native function calls** (`\count()`, `\is_numeric(...)`), `self::assert*` in tests. Methods that override or implement a parent/interface method carry `#[\Override]` (enforced by PHPStan's `checkMissingOverrideMethodAttribute`).

PHPStan must stay at zero errors without a baseline. The few `@phpstan-ignore` comments are deliberate and carry a reason; don't add new ones to silence real findings. In tests, PHPStan tracks `$obj['x'] = …` assignments on ArrayAccess objects and then mis-types later reads; use `offsetSet()` or constructor arguments there.

Do not enable PHP-CS-Fixer's `strict_comparison`. An automated `==`→`===` sweep (commit bbe82cf) once silently broke `equalTo` and the division-by-zero guards.

## Architecture

Everything evaluates against a `Context` in two roles, split by interface:

- **`Proposition`** (`evaluate(Context): bool`) — things that are true or false. `Rule` is itself a Proposition (condition + optional action + optional name), so rules nest inside logical operators.
- **`VariableOperand`** (`prepareValue(Context): Value`) — things that produce a value. `Variable` resolves by name from the Context, falling back to its default value (which may itself be a VariableOperand).

`Value` (immutable, `readonly`) holds the original comparison/arithmetic/string logic and `Set extends Value` holds set logic. Newer operators keep their logic in the operator class. Shared conversions (string, number, list, date, interval) live in `Internal\Coerce`.

Semantics that are deliberate (chosen by the project owner) and covered by tests:
- `equalTo` is **strict** (`===`), `sameAs` is **loose** (`==`). This is the reverse of PHPUnit's naming.
- `Set` membership is **type-sensitive**. Every member gets an identity key from `Set::keyOf()`: `serialize()` for scalars, `spl_object_id` for objects, sorted member keys for nested Sets (so they compare order-independently). All set operations are keyed-array operations on those keys. Never go back to `array_unique`/`array_diff`, which compare by string cast. `in`/`notIn` use the same semantics.
- `modulo` uses `%` for two ints and `fmod()` otherwise. Any zero divisor throws `DivisionByZeroException`.
- String operators treat `null` as matching nothing, convert int/float/Stringable, and throw `InvalidOperandException` for anything else.
- Case-insensitive operators fold both sides with `$context->caseFolder()`. The default `CaseFolding\Utf8CaseFolder` does Unicode canonical caseless matching: NFD, `mb_convert_case(MB_CASE_FOLD)` (full folding, so ß = ss), NFC. Normalization goes through `Internal\Unicode`, which relies on `\Normalizer` always existing (ext-intl, or the polyfill dependency); `CaseFolderTest::testNormalizesWithoutIntl` checks the polyfill path in a separate PHP process. Keep the NFC at the end: an NFD result would make `café` contain `cafe`. `GreekCaseFolder`, `FrenchCaseFolder` and `SpanishCaseFolder` extend `AccentInsensitiveCaseFolder`, which decomposes, removes the combining marks matched by `accentPattern()` and folds; Spanish must keep U+0303 (ñ). Folding stays on mbstring on purpose: ext-intl has no full case folding (`IntlChar::foldCase` is per code point, so ß stays ß; `Transliterator` *-Lower lowercases), and `Collator` compares equality but has no substring search. Languages without special rules (en, de, it, nl, pt) map to `Utf8CaseFolder` so that setting them is not an error. az/crh/gag map to `TurkishCaseFolder`, which composes (NFC) before replacing I/İ. `new Context(language: 'tr')` selects `TurkishCaseFolder` via `CaseFolderFactory`, where I = ı and İ = i but I ≠ i and İ ≠ ı. Each language gets its own `CaseFolder` class plus a `CaseFolderFactory::BUILT_INS` entry; language codes are normalised to their primary subtag (`tr-TR` → `tr`). The language lives on the Context, not in rules or JSON.
- Relative date operators take "now" from `Context::now()`, which comes from an injectable `Clock` (default `SystemClock`). Tests use `tests/Fixtures/FrozenClock`.

### Operators (`src/Operator/`) and the registry

Each operator is one class. `Operator<TOperand>` stores operands and validates their count with the `Cardinality` enum (`Unary`/`Binary`/`Ternary`/`Multiple`). Too many operands throws on add (`pushOperand()`); too few throws from `getOperands()`. Tests build logical operators incrementally with `addProposition()`, so the lower bound can't be checked eagerly.

- `VariableOperator` (`@extends Operator<VariableOperand>`): a subclass implements either `Proposition` or `VariableOperand`.
- `PropositionOperator` → `LogicalOperator`: operands are Propositions, passed as an **array** to the constructor. `CountingOperator` (`atLeast`/`atMost`/`exactly`) also takes a count first.

`OperatorRegistry` is the single name ↔ class mapping (`BUILT_INS` plus `register()` / `registerNamespace()`). `RuleBuilder\Variable::__call`, `RuleSerializer` and `Explainer` all use it. `RuleBuilder\Variable` has **no hand-written fluent methods**: every call goes through `__call`, and the operators are documented with `@method` tags. `OperatorRegistryTest` fails if those tags and `BUILT_INS` drift apart.

**Adding a built-in operator** touches:
- a class in `src/Operator/`,
- an entry in `OperatorRegistry::BUILT_INS`,
- an `@method` tag on `RuleBuilder\Variable` (or a method on `RuleBuilder` for logical operators),
- tests (`tests/Operator/ValueOperatorsTest.php` has data-provider tables per category),
- the README operator tables.

### RuleBuilder DSL (`src/RuleBuilder*`)

`RuleBuilder` is `ArrayAccess`: `$rb['name']` returns a cached `RuleBuilder\Variable`, and `$rb['user']['roles']` returns a `RuleBuilder\VariableProperty`. Names must be strings. Both `VariableProperty` classes implement `PropertyReference` (`getParent()`), and delegate lookups to `PropertyResolver::resolve()`. The lookup order is:
1. public method (`method_exists` && `is_callable`, so private methods and `__call` are skipped; `is_callable` alone is true for any name on objects with `__call`),
2. a property that is set (magic `__isset`/`__get` included),
3. an `ArrayAccess` offset,
4. an array key,
5. the default.

### RuleSet, serialization, explanations

- `RuleSet`: priorities live in the set (`addRule($rule, $priority)`), not on the Rule. `executeRules`/`evaluateRules` take `MatchMode` (All/First/Last) × `RuleOrder` (Insertion/Priority). They iterate in order (reversed for Last) and stop at the first match for First/Last. `usort` is stable, so equal priorities keep insertion order.
- `RuleSerializer`: versioned JSON/array documents. Node kinds are `op`, `var`, `property`/`of`, `value` and `rule`. Anonymous Variables wrapping an operator (created by the fluent DSL) are exported as that operator. Actions are re-attached on import by rule name, nested rules included. `JSON_PRESERVE_ZERO_FRACTION` keeps floats typed; invalid UTF-8 is substituted (U+FFFD) rather than throwing, in both the serializer and `Explanation`. Errors are `SerializationException` with a node path.
- `Explainer` / `Rule::explain()` build an `Explanation` tree using the same path notation. Rule nodes take their result from the condition child: calling `Rule::evaluate()` there would recurse, because `Rule::evaluate()` builds an explanation on failure. On failure, `Rule::evaluate()` throws `EvaluationException`, which carries the rule name, failure path/trail and explanation, with the original error as `getPrevious()`. Nested rule failures are unwrapped so the cause is reported once.

### Exceptions

All implement `Exception\RuleEngineException` and extend the SPL class that was thrown before they existed (`\RuntimeException`, `\LogicException`, `\InvalidArgumentException`), so old catch blocks keep working. `tests/ExceptionTest.php` checks both types for the core throw sites; `SerializationException` and `EvaluationException` are covered by `RuleSerializerTest` and `ExplanationTest`. `@throws` tags name the specific library exception, not the SPL parent.

### Context

`Context` is a Pimple-style container. Closures and invokable objects are invoked lazily with the Context on every read. `share()` resolves once and then freezes the fact. `protect()` stores a callable as a literal value. Fact names must be strings or ints.

## Tests

Tests live in `tests/` under `D6N\RuleEngine\Test\`, mirror `src/`, and must end in `Test.php`. `tests/Functional/` has end-to-end DSL examples. Test doubles live in `tests/Fixtures/`. Use `CallCounter` rather than by-reference closure variables, which PHPStan can't track. Behavior changes and bug fixes need tests.
