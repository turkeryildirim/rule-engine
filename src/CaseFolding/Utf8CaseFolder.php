<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * Language-independent Unicode case folding; the default.
 *
 * Uses full case folding (MB_CASE_FOLD) rather than lowercasing, so that for
 * example "Straße" and "STRASSE" are equal.
 */
final class Utf8CaseFolder implements CaseFolder
{
    #[\Override]
    public function fold(string $value): string
    {
        return \mb_convert_case($value, \MB_CASE_FOLD, 'UTF-8');
    }
}
