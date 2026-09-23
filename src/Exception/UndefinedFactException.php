<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A fact was read from a Context that does not define it.
 */
class UndefinedFactException extends \InvalidArgumentException implements RuleEngineException
{
}
