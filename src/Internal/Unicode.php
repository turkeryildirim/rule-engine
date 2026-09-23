<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Internal;

/**
 * Unicode normalization, via ext-intl or symfony/polyfill-intl-normalizer.
 *
 * @internal
 */
final class Unicode
{
    /**
     * Canonical composition: "e" + U+0301 becomes "é".
     */
    public static function compose(string $value): string
    {
        return self::normalize($value, \Normalizer::FORM_C);
    }

    /**
     * Canonical decomposition: "é" becomes "e" + U+0301.
     */
    public static function decompose(string $value): string
    {
        return self::normalize($value, \Normalizer::FORM_D);
    }

    private static function normalize(string $value, int $form): string
    {
        $normalized = \Normalizer::normalize($value, $form);

        // false for invalid UTF-8; the caller folds the value as it is then
        return \is_string($normalized) ? $normalized : $value;
    }
}
