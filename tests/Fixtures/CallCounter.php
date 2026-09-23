<?php

declare(strict_types=1);

namespace Ruler\Test\Fixtures;

/**
 * An invokable spy that records how often, and with what, it was called.
 */
final class CallCounter
{
    public int $calls = 0;

    /** @var list<list<mixed>> */
    public array $arguments = [];

    public function __invoke(mixed ...$arguments): void
    {
        ++$this->calls;
        $this->arguments[] = \array_values($arguments);
    }
}
