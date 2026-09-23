<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Internal;

/**
 * Unicode normalization when the intl extension is available; a no-op otherwise.
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
        return self::normalize($value, 'C');
    }

    /**
     * Canonical decomposition: "é" becomes "e" + U+0301.
     */
    public static function decompose(string $value): string
    {
        return self::normalize($value, 'D');
    }

    /**
     * @param 'C'|'D' $form
     */
    private static function normalize(string $value, string $form): string
    {
        // Normalizer comes from ext-intl; invalid UTF-8 makes it return false
        $normalized = \class_exists(\Normalizer::class) ? \Normalizer::normalize($value, 'C' === $form ? \Normalizer::FORM_C : \Normalizer::FORM_D) : $value;

        return \is_string($normalized) ? $normalized : $value;
    }
}
