<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

use D6N\RuleEngine\Internal\Unicode;

/**
 * Turkish case folding: dotless I/ı and dotted İ/i are separate letters.
 *
 *   I = ı    İ = i    (and I ≠ i, İ ≠ ı)
 *
 * So "KIR" = "kır" and "KİR" = "kir", but "kır" ≠ "kir". All other letters
 * follow Utf8CaseFolder. Also used for Azerbaijani, Crimean Tatar and Gagauz,
 * which share the rule.
 */
final class TurkishCaseFolder implements CaseFolder
{
    private readonly Utf8CaseFolder $unicode;

    public function __construct()
    {
        $this->unicode = new Utf8CaseFolder();
    }

    #[\Override]
    public function fold(string $value): string
    {
        // Compose first, so that "I" + U+0307 (a decomposed "İ") is recognised as "İ"
        return $this->unicode->fold(\str_replace(['I', 'İ'], ['ı', 'i'], Unicode::compose($value)));
    }
}
