<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

interface VariableOperand
{
    public function prepareValue(Context $context): Value;
}
