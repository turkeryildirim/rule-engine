<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * An operator received a value of a type it cannot work with.
 */
class InvalidOperandException extends \RuntimeException implements RuleEngineException
{
}
