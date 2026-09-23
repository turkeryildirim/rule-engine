<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * French case folding: accents are ignored, because capitals are often
 * written without them ("ETAT" for "État").
 *
 * Removes the acute, grave, circumflex, diaeresis and cedilla from Latin
 * letters: "ÉTAT" = "état", "GARCON" = "garçon", "NOEL" = "Noël".
 * The ligatures œ and æ are kept. As a consequence, words that differ only
 * in their accents match too: "côte" = "cote" = "coté".
 */
final class FrenchCaseFolder extends AccentInsensitiveCaseFolder
{
    #[\Override]
    protected function accentPattern(): string
    {
        return '/(\p{Latin})[\x{0300}\x{0301}\x{0302}\x{0308}\x{0327}]+/u';
    }
}
