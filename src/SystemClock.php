<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * The default Clock: the current system time.
 */
final class SystemClock implements Clock
{
    #[\Override]
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
