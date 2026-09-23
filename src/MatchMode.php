<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * Which matching Rules of a RuleSet are executed or returned.
 */
enum MatchMode
{
    /** Every Rule whose condition holds. */
    case All;

    /** Only the first Rule whose condition holds; later Rules are not evaluated. */
    case First;

    /** Only the last Rule whose condition holds; Rules are evaluated from the end. */
    case Last;
}
