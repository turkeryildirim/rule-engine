<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A fact, variable or property name has an unsupported type.
 */
class InvalidNameException extends \InvalidArgumentException implements RuleEngineException
{
}
