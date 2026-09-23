<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A value that must be a Closure or invokable object is not.
 */
class NotCallableException extends \InvalidArgumentException implements RuleEngineException
{
}
