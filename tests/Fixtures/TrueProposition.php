<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

use D6N\RuleEngine\Context;
use D6N\RuleEngine\Proposition;

class TrueProposition implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        return true;
    }
}
