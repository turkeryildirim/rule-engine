<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * Library metadata.
 *
 * VERSION is the released package version. CI tags and releases it as
 * "v" . VERSION when it reaches main. It is unrelated to
 * RuleSerializer::VERSION, the JSON document format version.
 */
final class RuleEngine
{
    public const string VERSION = '1.0.0';
}
