<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * Marker implemented by every exception this library throws.
 *
 * Each exception also extends the SPL exception that was thrown before it
 * existed (\RuntimeException, \LogicException, \InvalidArgumentException),
 * so existing catch blocks keep working.
 */
interface RuleEngineException extends \Throwable
{
}
