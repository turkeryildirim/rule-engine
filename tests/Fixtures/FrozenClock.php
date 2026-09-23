<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

use D6N\RuleEngine\Clock;

final readonly class FrozenClock implements Clock
{
    private \DateTimeImmutable $now;

    public function __construct(string $now = '2026-09-23 12:00:00 UTC')
    {
        $this->now = new \DateTimeImmutable($now);
    }

    #[\Override]
    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
