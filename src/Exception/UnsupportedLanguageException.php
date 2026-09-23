<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * No CaseFolder is registered for the requested language.
 */
class UnsupportedLanguageException extends \InvalidArgumentException implements RuleEngineException
{
}
