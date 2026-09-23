<?php

declare(strict_types=1);

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D6N\RuleEngine;

/**
 * A Ruler Set.
 *
 * An immutable collection of unique members, a special case of Value which can
 * be compared by the set operators.
 *
 * Membership is type-sensitive: 1, "1", 1.0 and true are four different members.
 * Nested arrays become nested Sets, compared by content regardless of order.
 * Objects are compared by identity.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 */
class Set extends Value implements \Countable
{
    /** @var array<string, mixed> members indexed by their identity key */
    private readonly array $members;

    /**
     * Set constructor.
     *
     * Arrays become a Set of their values (keys are discarded), null becomes an
     * empty Set, and any other value becomes a single-member Set.
     *
     * @param mixed $set Immutable value represented by this Set
     */
    public function __construct(mixed $set)
    {
        $members = [];
        foreach (self::toList($set) as $item) {
            $member = self::normalize($item);
            $members[self::keyOf($member)] ??= $member;
        }

        $this->members = $members;
        parent::__construct(\array_values($members));
    }

    /**
     * An order-independent representation of this Set's contents.
     */
    #[\Override]
    public function __toString(): string
    {
        return self::keyOf($this);
    }

    /**
     * @return list<mixed>
     */
    #[\Override]
    public function getValue(): array
    {
        return \array_values($this->members);
    }

    #[\Override]
    public function getSet(): self
    {
        return $this;
    }

    /**
     * Whether the given value is a member of this Set.
     */
    public function setContains(Value $value): bool
    {
        return \array_key_exists(self::keyOf(self::normalize($value->getValue())), $this->members);
    }

    public function union(Value ...$sets): self
    {
        $union = $this->members;
        foreach ($sets as $set) {
            $union += $set->getSet()->members;
        }

        return self::fromMembers($union);
    }

    public function intersect(Value ...$sets): self
    {
        $intersect = $this->members;
        foreach ($sets as $set) {
            $intersect = \array_intersect_key($intersect, $set->getSet()->members);
        }

        return self::fromMembers($intersect);
    }

    public function complement(Value ...$sets): self
    {
        $complement = $this->members;
        foreach ($sets as $set) {
            $complement = \array_diff_key($complement, $set->getSet()->members);
        }

        return self::fromMembers($complement);
    }

    public function symmetricDifference(Value $set): self
    {
        $other = $set->getSet();

        return $this->complement($other)->union($other->complement($this));
    }

    /**
     * Numeric minimum value in this Set, or null if the Set is empty.
     *
     * @throws \RuntimeException if this Set contains non-numeric members
     */
    public function min(): int|float|string|null
    {
        return [] === $this->members ? null : \min($this->numericMembers('min'));
    }

    /**
     * Numeric maximum value in this Set, or null if the Set is empty.
     *
     * @throws \RuntimeException if this Set contains non-numeric members
     */
    public function max(): int|float|string|null
    {
        return [] === $this->members ? null : \max($this->numericMembers('max'));
    }

    /**
     * Whether every member of the given Set is also a member of this Set.
     */
    public function containsSubset(self $set): bool
    {
        return [] === \array_diff_key($set->members, $this->members);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->members);
    }

    /**
     * @return non-empty-list<int|float|string>
     *
     * @throws \RuntimeException if this Set contains non-numeric members
     */
    private function numericMembers(string $operation): array
    {
        $numbers = [];
        foreach ($this->members as $member) {
            if (!\is_numeric($member)) {
                throw new \RuntimeException($operation.': all values must be numeric');
            }
            $numbers[] = $member;
        }

        \assert([] !== $numbers);

        return $numbers;
    }

    /**
     * @param array<string, mixed> $members
     */
    private static function fromMembers(array $members): self
    {
        return new self(\array_values($members));
    }

    /**
     * @return array<mixed>
     */
    private static function toList(mixed $set): array
    {
        return match (true) {
            null === $set   => [],
            \is_array($set) => $set,
            default         => [$set],
        };
    }

    /**
     * Nested arrays become Sets and Values are unwrapped, so equal members get equal keys.
     */
    private static function normalize(mixed $member): mixed
    {
        return match (true) {
            \is_array($member)                                   => new self($member),
            $member instanceof Value && !$member instanceof self => $member->getValue(),
            default                                              => $member,
        };
    }

    /**
     * A key that is equal for two members exactly when they are the same member.
     */
    private static function keyOf(mixed $member): string
    {
        if ($member instanceof self) {
            $keys = \array_keys($member->members);
            \sort($keys);

            return 'S'.\serialize($keys);
        }

        return match (true) {
            \is_object($member)   => 'O'.\spl_object_id($member),
            \is_resource($member) => 'R'.\get_resource_id($member),
            default               => 'V'.\serialize($member),
        };
    }
}
