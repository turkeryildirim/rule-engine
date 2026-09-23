<?php

declare(strict_types=1);

namespace Ruler\Test\Fixtures;

use Ruler\Context;
use Ruler\Proposition;

class FalseProposition implements Proposition
{
    #[\Override]
    public function evaluate(Context $context): bool
    {
        return false;
    }
}
