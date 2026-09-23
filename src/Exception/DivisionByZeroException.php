<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * Division or modulo by zero, or zero raised to a negative power.
 */
class DivisionByZeroException extends ArithmeticException implements RuleEngineException
{
}
