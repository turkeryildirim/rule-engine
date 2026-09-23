<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * Turkish case folding: dotless I/ı and dotted İ/i are separate letters.
 *
 *   I = ı    İ = i    (and I ≠ i, İ ≠ ı)
 *
 * So "KIR" = "kır" and "KİR" = "kir", but "kır" ≠ "kir". All other letters
 * follow Utf8CaseFolder.
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
        return $this->unicode->fold(\str_replace(['I', 'İ'], ['ı', 'i'], $value));
    }
}
