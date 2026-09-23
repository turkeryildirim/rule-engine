<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Exception;

/**
 * A rule could not be exported to, or imported from, JSON.
 *
 * The message names the offending node, e.g. "rules[0].condition.operands[1]".
 */
class SerializationException extends \InvalidArgumentException implements RuleEngineException
{
    public function __construct(string $message, private readonly string $path = '', ?\Throwable $previous = null)
    {
        parent::__construct('' === $path ? $message : \sprintf('%s (at %s)', $message, $path), 0, $previous);
    }

    /**
     * Where in the document the problem is, e.g. "rules[0].condition.operands[1]".
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
