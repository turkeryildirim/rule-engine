<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A shared fact was redefined after it had been resolved.
 */
class FrozenFactException extends \RuntimeException implements RuleEngineException
{
}
