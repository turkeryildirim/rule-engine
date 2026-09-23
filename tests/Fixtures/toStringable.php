<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

final readonly class toStringable implements \Stringable
{
    public function __construct(private int|string $thingy)
    {
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->thingy;
    }
}
