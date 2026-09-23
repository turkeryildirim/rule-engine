<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * No operator is registered under the requested name.
 */
class UnknownOperatorException extends \LogicException implements RuleEngineException
{
}
