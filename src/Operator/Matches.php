<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Exception\InvalidOperandException;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;

/**
 * True when the value matches the PCRE pattern, e.g. "/^[A-Z]{2}\\d+$/". A null value matches nothing.
 */
class Matches extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$subject, $pattern] = $this->getOperands();
        $subject = Coerce::string($subject->prepareValue($context)->getValue());
        $pattern = (string) Coerce::string($pattern->prepareValue($context)->getValue());

        \set_error_handler(static fn (int $type, string $message): never => throw new InvalidOperandException('Invalid regular expression: '.$message));
        try {
            $matched = \preg_match($pattern, $subject ?? '');
        } finally {
            \restore_error_handler();
        }

        return null !== $subject && 1 === $matched;
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
