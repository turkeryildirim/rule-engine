<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

use D6N\RuleEngine\Exception\UnsupportedLanguageException;

/**
 * Creates the CaseFolder for a language.
 *
 * Languages are identified by their ISO 639-1 code; region suffixes are
 * ignored, so "tr", "tr-TR" and "tr_TR" all give the Turkish folder.
 * Languages without special rules don't need an entry: use the default
 * Utf8CaseFolder by not setting a language at all.
 *
 *     $factory = new CaseFolderFactory();
 *     $factory->register('el', GreekCaseFolder::class);
 *     $context = new Context($facts, language: $factory->create('el'));
 */
final class CaseFolderFactory
{
    /**
     * @var array<string, class-string<CaseFolder>>
     */
    public const array BUILT_INS = [
        'tr' => TurkishCaseFolder::class,
    ];

    /** @var array<string, class-string<CaseFolder>> */
    private array $folders = self::BUILT_INS;

    /**
     * Add or replace the CaseFolder for a language.
     *
     * @param class-string $class a CaseFolder with a constructor that takes no arguments
     *
     * @throws UnsupportedLanguageException if the class is not a CaseFolder
     */
    public function register(string $language, string $class): void
    {
        if (!\is_subclass_of($class, CaseFolder::class)) {
            throw new UnsupportedLanguageException(\sprintf('Cannot register "%s": %s is not a CaseFolder.', $language, $class));
        }

        $this->folders[self::normalize($language)] = $class;
    }

    /**
     * @throws UnsupportedLanguageException if no CaseFolder is registered for the language
     */
    public function create(string $language): CaseFolder
    {
        $class = $this->folders[self::normalize($language)] ?? null;
        if (null === $class) {
            throw new UnsupportedLanguageException(\sprintf('No case folding rules for language "%s"; supported: %s. Omit the language to use Unicode defaults.', $language, \implode(', ', \array_keys($this->folders))));
        }

        return new $class();
    }

    /**
     * "tr-TR", "tr_TR" and "TR" become "tr".
     */
    private static function normalize(string $language): string
    {
        return \strtolower(\explode('-', \str_replace('_', '-', $language), 2)[0]);
    }
}
