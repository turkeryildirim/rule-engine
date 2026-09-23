<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * An operator was given too many or too few operands.
 */
class OperandCountException extends \LogicException implements RuleEngineException
{
}
