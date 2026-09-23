<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

use D6N\RuleEngine\Internal\Unicode;

/**
 * Language-independent Unicode case folding; the default.
 *
 * Implements Unicode canonical caseless matching: the value is decomposed,
 * fully case-folded (MB_CASE_FOLD, so "Straße" = "STRASSE") and recomposed.
 * Canonically equivalent spellings therefore match, e.g. "café" written with
 * a precomposed "é" and with "e" + a combining accent. Normalization needs the
 * intl extension and is skipped without it.
 */
final class Utf8CaseFolder implements CaseFolder
{
    #[\Override]
    public function fold(string $value): string
    {
        return Unicode::compose(\mb_convert_case(Unicode::decompose($value), \MB_CASE_FOLD, 'UTF-8'));
    }
}
