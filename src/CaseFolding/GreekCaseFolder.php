<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

use D6N\RuleEngine\Internal\Unicode;

/**
 * Greek case folding: accents on Greek letters are ignored.
 *
 * Greek text written in capitals normally drops the tonos and other accents,
 * so "Αθήνα" and "ΑΘΗΝΑ" must match. Diacritics on non-Greek letters are
 * kept. The final sigma (ς) already folds to σ under Unicode rules.
 */
final class GreekCaseFolder implements CaseFolder
{
    private readonly Utf8CaseFolder $unicode;

    public function __construct()
    {
        $this->unicode = new Utf8CaseFolder();
    }

    #[\Override]
    public function fold(string $value): string
    {
        $withoutAccents = \preg_replace('/(\p{Greek})\p{Mn}+/u', '$1', Unicode::decompose($value));

        // preg_replace() returns null for invalid UTF-8; fold the value as it is then
        return $this->unicode->fold($withoutAccents ?? $value);
    }
}
