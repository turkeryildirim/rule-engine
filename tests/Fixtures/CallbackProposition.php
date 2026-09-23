<?php

declare(strict_types=1);

namespace Ruler\Test\Fixtures;

use Ruler\Context;
use Ruler\Proposition;

final readonly class CallbackProposition implements Proposition
{
    /** @var \Closure(Context): bool */
    private \Closure $callback;

    /**
     * @param callable(Context): bool $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    #[\Override]
    public function evaluate(Context $context): bool
    {
        return ($this->callback)($context);
    }
}
