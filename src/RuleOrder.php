<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * The order in which a RuleSet walks its Rules.
 */
enum RuleOrder
{
    /** The order in which the Rules were added. */
    case Insertion;

    /** Highest priority first; Rules with equal priority keep their insertion order. */
    case Priority;
}
