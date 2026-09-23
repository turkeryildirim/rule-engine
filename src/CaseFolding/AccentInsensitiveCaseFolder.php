<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

use D6N\RuleEngine\Internal\Unicode;

/**
 * Base for languages that drop some accents when writing in capitals.
 *
 * The value is decomposed, the language's accents are removed, and the rest
 * is folded by Utf8CaseFolder.
 */
abstract class AccentInsensitiveCaseFolder implements CaseFolder
{
    private readonly Utf8CaseFolder $unicode;

    public function __construct()
    {
        $this->unicode = new Utf8CaseFolder();
    }

    #[\Override]
    public function fold(string $value): string
    {
        $withoutAccents = \preg_replace($this->accentPattern(), '$1', Unicode::decompose($value));

        // preg_replace() returns null for invalid UTF-8; fold the value as it is then
        return $this->unicode->fold($withoutAccents ?? $value);
    }

    /**
     * A /u pattern whose first group is the letter to keep, followed by the
     * combining marks to remove from it.
     */
    abstract protected function accentPattern(): string;
}
