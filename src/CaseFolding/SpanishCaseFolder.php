<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * Spanish case folding: stress accents and the diaeresis are ignored,
 * because capitals are often written without them ("ARBOL" for "Árbol").
 *
 * Removes the acute accent and the diaeresis: "ARBOL" = "árbol",
 * "PINGUINO" = "pingüino". The tilde is kept, because ñ is a letter of its
 * own: "año" ≠ "ano". Words that differ only in their stress accent match:
 * "papa" = "papá".
 */
final class SpanishCaseFolder extends AccentInsensitiveCaseFolder
{
    #[\Override]
    protected function accentPattern(): string
    {
        return '/(\p{Latin})[\x{0301}\x{0308}]+/u';
    }
}
