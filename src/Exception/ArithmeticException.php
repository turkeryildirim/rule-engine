<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A math operator received a non-numeric value.
 */
class ArithmeticException extends \RuntimeException implements RuleEngineException
{
}
