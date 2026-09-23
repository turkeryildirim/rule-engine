<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\CaseFolding;

use D6N\RuleEngine\CaseFolding\CaseFolder;
use D6N\RuleEngine\CaseFolding\CaseFolderFactory;
use D6N\RuleEngine\CaseFolding\TurkishCaseFolder;
use D6N\RuleEngine\CaseFolding\Utf8CaseFolder;
use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\UnsupportedLanguageException;
use D6N\RuleEngine\RuleBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CaseFolderTest extends TestCase
{
    /**
     * I matches I or ı; İ matches İ or i; ı matches I or ı; i matches i or İ.
     */
    #[DataProvider('turkishPairs')]
    public function testTurkishRules(string $a, string $b, bool $equal): void
    {
        $folder = new TurkishCaseFolder();

        self::assertSame($equal, $folder->fold($a) === $folder->fold($b));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function turkishPairs(): iterable
    {
        yield 'I = I' => ['I', 'I', true];
        yield 'I = ı' => ['I', 'ı', true];
        yield 'I ≠ i' => ['I', 'i', false];
        yield 'I ≠ İ' => ['I', 'İ', false];
        yield 'İ = İ' => ['İ', 'İ', true];
        yield 'İ = i' => ['İ', 'i', true];
        yield 'İ ≠ ı' => ['İ', 'ı', false];
        yield 'ı = ı' => ['ı', 'ı', true];
        yield 'ı ≠ i' => ['ı', 'i', false];
        yield 'i = i' => ['i', 'i', true];
        yield 'kır ≠ kir' => ['kır', 'kir', false];
        yield 'KIR = kır' => ['KIR', 'kır', true];
        yield 'KİR = kir' => ['KİR', 'kir', true];
        yield 'İSTANBUL = istanbul' => ['İSTANBUL', 'istanbul', true];
        yield 'ISPARTA = ısparta' => ['ISPARTA', 'ısparta', true];
        yield 'ÇAĞRI = çağrı' => ['ÇAĞRI', 'çağrı', true];
        yield 'ÖĞÜŞ = öğüş' => ['ÖĞÜŞ', 'öğüş', true];
    }

    #[DataProvider('unicodePairs')]
    public function testUnicodeDefaults(string $a, string $b, bool $equal): void
    {
        $folder = new Utf8CaseFolder();

        self::assertSame($equal, $folder->fold($a) === $folder->fold($b));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function unicodePairs(): iterable
    {
        yield 'ASCII' => ['Hello', 'hELLO', true];
        yield 'ß = ss' => ['Straße', 'STRASSE', true];
        yield 'Ç = ç' => ['ÇAĞ', 'çağ', true];
        yield 'Σ = σ' => ['ΣΟΦΙΑ', 'σοφια', true];
        yield 'I = i' => ['I', 'i', true];
        yield 'İ ≠ i (no rules)' => ['İ', 'i', false];
        yield 'I ≠ ı (no rules)' => ['I', 'ı', false];
    }

    #[DataProvider('languageCodes')]
    public function testFactoryAcceptsLanguageCodesWithRegions(string $code): void
    {
        self::assertInstanceOf(TurkishCaseFolder::class, new CaseFolderFactory()->create($code));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function languageCodes(): iterable
    {
        yield 'language' => ['tr'];
        yield 'upper case' => ['TR'];
        yield 'with region' => ['tr-TR'];
        yield 'with POSIX region' => ['tr_TR'];
        yield 'with script, region' => ['tr-Latn-TR'];
    }

    public function testFactoryRejectsUnknownLanguages(): void
    {
        $this->expectException(UnsupportedLanguageException::class);
        $this->expectExceptionMessage('No case folding rules for language "xx"; supported: tr.');

        new CaseFolderFactory()->create('xx');
    }

    public function testNewLanguagesCanBeRegistered(): void
    {
        $factory = new CaseFolderFactory();
        $factory->register('en-GB', Utf8CaseFolder::class);

        self::assertInstanceOf(Utf8CaseFolder::class, $factory->create('en'));
    }

    public function testOnlyCaseFoldersCanBeRegistered(): void
    {
        $this->expectException(UnsupportedLanguageException::class);
        $this->expectExceptionMessage('Cannot register "el": stdClass is not a CaseFolder.');

        new CaseFolderFactory()->register('el', \stdClass::class);
    }

    public function testContextChoosesTheRules(): void
    {
        $custom = new class implements CaseFolder {
            #[\Override]
            public function fold(string $value): string
            {
                return 'same';
            }
        };

        self::assertInstanceOf(Utf8CaseFolder::class, new Context()->caseFolder());
        self::assertInstanceOf(TurkishCaseFolder::class, new Context(language: 'tr')->caseFolder());
        self::assertSame($custom, new Context(language: $custom)->caseFolder());
    }

    public function testContextRejectsUnknownLanguages(): void
    {
        $this->expectException(UnsupportedLanguageException::class);

        new Context(language: 'xx');
    }

    /**
     * @param 'default'|'tr' $language
     */
    #[DataProvider('operatorsByLanguage')]
    public function testInsensitiveOperatorsUseTheContextLanguage(string $operator, string $value, string $argument, string $language, bool $expected): void
    {
        $rb = new RuleBuilder();
        $context = new Context(['v' => $value], language: 'tr' === $language ? 'tr' : null);

        $proposition = $rb['v']->__call($operator, [$argument]);
        self::assertInstanceOf(\D6N\RuleEngine\Proposition::class, $proposition);
        self::assertSame($expected, $proposition->evaluate($context));
    }

    /**
     * @return iterable<string, array{string, string, string, 'default'|'tr', bool}>
     */
    public static function operatorsByLanguage(): iterable
    {
        yield 'contains, default' => ['stringContainsInsensitive', 'İSTANBUL', 'istanbul', 'default', false];
        yield 'contains, tr' => ['stringContainsInsensitive', 'İSTANBUL', 'istanbul', 'tr', true];
        yield 'contains, tr, kır ≠ kir' => ['stringContainsInsensitive', 'KIR', 'kir', 'tr', false];
        yield 'does not contain, tr' => ['stringDoesNotContainInsensitive', 'KIR', 'kır', 'tr', false];
        yield 'starts with, default' => ['startsWithInsensitive', 'ISPARTA', 'ıs', 'default', false];
        yield 'starts with, tr' => ['startsWithInsensitive', 'ISPARTA', 'ıs', 'tr', true];
        yield 'ends with, tr' => ['endsWithInsensitive', 'ÇAĞRI', 'rı', 'tr', true];
        yield 'ends with, tr, ı ≠ i' => ['endsWithInsensitive', 'ÇAĞRI', 'ri', 'tr', false];
        yield 'ends with, default, I=i' => ['endsWithInsensitive', 'ÇAĞRI', 'ri', 'default', true];
    }
}
