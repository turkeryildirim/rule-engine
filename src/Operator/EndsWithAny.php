<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Operator;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Internal\Coerce;
use D6N\RuleEngine\Proposition;
use D6N\RuleEngine\Value;

/**
 * True when the string ends with at least one of the given suffixes.
 */
class EndsWithAny extends VariableOperator implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        [$subject, $candidates] = $this->getOperands();
        $subject = $subject->prepareValue($context);

        return \array_any(
            Coerce::list($candidates->prepareValue($context)->getValue()),
            static fn (mixed $candidate): bool => $subject->endsWith(new Value($candidate)),
        );
    }

    #[\Override]
    protected function getOperandCardinality(): Cardinality
    {
        return Cardinality::Binary;
    }
}
