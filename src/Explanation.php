<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * One node of an evaluated rule tree: what it is, what it produced, and its operands.
 *
 * Built by Explainer; see Rule::explain().
 */
final readonly class Explanation implements \JsonSerializable, \Stringable
{
    /**
     * @param string            $path     where the node is, in the same notation as exported JSON, e.g. "condition.operands[1]"
     * @param string            $type     "rule", "operator", "var", "property" or "value"
     * @param string|null       $name     rule name, operator name, fact name or property name
     * @param mixed             $result   true/false for propositions, the produced value otherwise; null when $error is set
     * @param list<Explanation> $children
     */
    public function __construct(
        public string $path,
        public string $type,
        public ?string $name,
        public mixed $result,
        public ?\Throwable $error = null,
        public array $children = [],
    ) {
    }

    public function failed(): bool
    {
        return null !== $this->error;
    }

    /**
     * The chain of nodes from this one down to the deepest node that failed;
     * empty if nothing failed.
     *
     * @return list<Explanation>
     */
    public function failureTrail(): array
    {
        foreach ($this->children as $child) {
            $trail = $child->failureTrail();
            if ([] !== $trail) {
                return [$this, ...$trail];
            }
        }

        return $this->failed() ? [$this] : [];
    }

    /**
     * @return array{path: string, type: string, name: string|null, result: mixed, error?: string, children?: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        $array = ['path' => $this->path, 'type' => $this->type, 'name' => $this->name, 'result' => self::describe($this->result)];
        if (null !== $this->error) {
            $array['error'] = $this->error->getMessage();
        }
        if ([] !== $this->children) {
            $array['children'] = \array_map(static fn (self $child): array => $child->toArray(), $this->children);
        }

        return $array;
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * An indented tree, one node per line, e.g.
     *
     *   rule freeShipping: false
     *     logicalAnd: false
     *       greaterThanOrEqualTo: false
     *         var orderTotal: 20
     *         value: 50
     */
    #[\Override]
    public function __toString(): string
    {
        return \implode("\n", $this->lines(0));
    }

    /**
     * @return list<string>
     */
    private function lines(int $depth): array
    {
        $label = \in_array($this->type, ['operator', 'value'], true) ? ($this->name ?? $this->type) : \trim($this->type.' '.$this->name);
        $outcome = null === $this->error ? self::format($this->result) : 'ERROR '.$this->error->getMessage();
        $lines = [\str_repeat('  ', $depth).$label.': '.$outcome];
        foreach ($this->children as $child) {
            \array_push($lines, ...$child->lines($depth + 1));
        }

        return $lines;
    }

    private static function describe(mixed $value): mixed
    {
        return match (true) {
            \is_float($value) && !\is_finite($value) => (string) $value,
            null === $value, \is_scalar($value)      => $value,
            \is_array($value)                        => \array_map(self::describe(...), $value),
            $value instanceof \DateTimeInterface     => $value->format(\DATE_ATOM),
            default                                  => \get_debug_type($value),
        };
    }

    private static function format(mixed $value): string
    {
        return \json_encode(self::describe($value), \JSON_THROW_ON_ERROR | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION);
    }
}
