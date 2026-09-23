<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * Greek case folding: accents on Greek letters are ignored.
 *
 * Greek text written in capitals normally drops the tonos and other accents,
 * so "Αθήνα" and "ΑΘΗΝΑ" must match. Diacritics on non-Greek letters are
 * kept. The final sigma (ς) already folds to σ under Unicode rules.
 */
final class GreekCaseFolder extends AccentInsensitiveCaseFolder
{
    #[\Override]
    protected function accentPattern(): string
    {
        return '/(\p{Greek})\p{Mn}+/u';
    }
}
