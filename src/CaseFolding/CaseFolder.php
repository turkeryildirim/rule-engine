<?php

declare(strict_types=1);

namespace D6N\RuleEngine\CaseFolding;

/**
 * Maps a string to a form in which letters that differ only in case are equal.
 *
 * The case-insensitive string operators fold both sides with the Context's
 * CaseFolder and compare the results. Languages whose case rules differ from
 * Unicode's defaults get their own implementation; see CaseFolderFactory.
 */
interface CaseFolder
{
    public function fold(string $value): string;
}
