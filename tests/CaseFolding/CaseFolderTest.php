<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\CaseFolding;

use D6N\RuleEngine\CaseFolding\CaseFolder;
use D6N\RuleEngine\CaseFolding\CaseFolderFactory;
use D6N\RuleEngine\CaseFolding\FrenchCaseFolder;
use D6N\RuleEngine\CaseFolding\GreekCaseFolder;
use D6N\RuleEngine\CaseFolding\SpanishCaseFolder;
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
        yield 'decomposed İ = i' => ["I\u{0307}STANBUL", 'istanbul', true];
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
        yield 'ẞ = ss' => ['STRAẞE', 'strasse', true];
        yield 'composed = decomposed é' => ['café', "CAFE\u{0301}", true];
        yield 'ñ = Ñ' => ['AÑO', 'año', true];
        yield 'ñ ≠ n' => ['año', 'ano', false];
        yield 'é ≠ e' => ['côte', 'cote', false];
        yield 'œ = Œ' => ['ŒUVRE', 'œuvre', true];
        yield 'Greek accents kept' => ['Αθήνα', 'ΑΘΗΝΑ', false];
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
        $this->expectExceptionMessage('No case folding rules for language "xx"; supported: tr, az, crh, gag, el, es, fr, en, de, it, nl, pt.');

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

    #[DataProvider('greekPairs')]
    public function testGreekRules(string $a, string $b, bool $equal): void
    {
        $folder = new GreekCaseFolder();

        self::assertSame($equal, $folder->fold($a) === $folder->fold($b));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function greekPairs(): iterable
    {
        yield 'tonos dropped in capitals' => ['Αθήνα', 'ΑΘΗΝΑ', true];
        yield 'final sigma' => ['οδός', 'ΟΔΟΣ', true];
        yield 'dialytika' => ['ΠΡΩΪΝΟ', 'πρωινό', true];
        yield 'polytonic' => ['Ἀθῆναι', 'ΑΘΗΝΑΙ', true];
        yield 'different words' => ['Αθήνα', 'Σπάρτη', false];
        yield 'Latin accents are kept' => ['CAFÉ', 'cafe', false];
    }

    /**
     * @param class-string<CaseFolder> $expected
     */
    #[DataProvider('mappedLanguages')]
    public function testLanguagesMapToTheirRules(string $language, string $expected): void
    {
        self::assertInstanceOf($expected, new CaseFolderFactory()->create($language));
    }

    /**
     * @return iterable<string, array{string, class-string<CaseFolder>}>
     */
    public static function mappedLanguages(): iterable
    {
        yield 'Azerbaijani' => ['az', TurkishCaseFolder::class];
        yield 'Crimean Tatar' => ['crh', TurkishCaseFolder::class];
        yield 'Gagauz' => ['gag', TurkishCaseFolder::class];
        yield 'Greek' => ['el-GR', GreekCaseFolder::class];
        yield 'Spanish' => ['es', SpanishCaseFolder::class];
        yield 'Portuguese' => ['pt-BR', Utf8CaseFolder::class];
        yield 'Italian' => ['it', Utf8CaseFolder::class];
        yield 'French' => ['fr_FR', FrenchCaseFolder::class];
    }

    public function testInvalidUtf8IsFoldedWithoutNormalizing(): void
    {
        foreach ([new Utf8CaseFolder(), new TurkishCaseFolder(), new GreekCaseFolder(), new FrenchCaseFolder(), new SpanishCaseFolder()] as $folder) {
            self::assertSame('caf?', $folder->fold("CAF\xE9"), $folder::class);
        }
    }

    #[DataProvider('frenchPairs')]
    public function testFrenchRules(string $a, string $b, bool $equal): void
    {
        $folder = new FrenchCaseFolder();

        self::assertSame($equal, $folder->fold($a) === $folder->fold($b));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function frenchPairs(): iterable
    {
        yield 'acute' => ['ETAT', 'état', true];
        yield 'grave' => ['A LA', 'à là', true];
        yield 'circumflex' => ['FORET', 'forêt', true];
        yield 'diaeresis' => ['NOEL', 'Noël', true];
        yield 'cedilla' => ['GARCON', 'garçon', true];
        yield 'with accents on caps' => ['ÉTAT', 'etat', true];
        yield 'ligature kept' => ['ŒUVRE', 'œuvre', true];
        yield 'ligature ≠ letters' => ['oeuvre', 'œuvre', false];
        yield 'accent-only difference' => ['côte', 'cote', true];
        yield 'different words' => ['état', 'étau', false];
    }

    #[DataProvider('spanishPairs')]
    public function testSpanishRules(string $a, string $b, bool $equal): void
    {
        $folder = new SpanishCaseFolder();

        self::assertSame($equal, $folder->fold($a) === $folder->fold($b));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function spanishPairs(): iterable
    {
        yield 'acute' => ['ARBOL', 'árbol', true];
        yield 'with accents on caps' => ['ÁRBOL', 'arbol', true];
        yield 'diaeresis' => ['PINGUINO', 'pingüino', true];
        yield 'ñ = Ñ' => ['AÑO', 'año', true];
        yield 'ñ ≠ n' => ['año', 'ano', false];
        yield 'ñ ≠ n in capitals' => ['ANO', 'año', false];
        yield 'accent-only difference' => ['papa', 'papá', true];
        yield 'other diacritics kept' => ['ç', 'c', false];
    }

    public function testFrenchAndSpanishThroughTheContext(): void
    {
        $rb = new RuleBuilder();

        self::assertTrue($rb['v']->stringContainsInsensitive('etat')->evaluate(new Context(['v' => "CHEF D'ÉTAT"], language: 'fr')));
        self::assertFalse($rb['v']->stringContainsInsensitive('etat')->evaluate(new Context(['v' => "CHEF D'ÉTAT"])));
        self::assertTrue($rb['v']->startsWithInsensitive('arbol')->evaluate(new Context(['v' => 'ÁRBOLES'], language: 'es-ES')));
        self::assertFalse($rb['v']->endsWithInsensitive('ano')->evaluate(new Context(['v' => 'FELIZ AÑO'], language: 'es')));
    }
}
