<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * The source of "now" for relative date operators such as withinLast().
 *
 * Its shape matches PSR-20, so a PSR-20 clock can be adapted with a one-line class.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
