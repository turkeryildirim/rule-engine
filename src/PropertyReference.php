<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

/**
 * A named property of another Variable, e.g. $rb['user']['roles'].
 *
 * Implemented by both VariableProperty classes, so tools such as the JSON
 * serializer can walk property chains without caring which one they got.
 */
interface PropertyReference extends VariableOperand
{
    public function getParent(): Variable;

    public function getName(): ?string;

    /**
     * The default used when the parent value has no such property.
     */
    public function getValue(): mixed;
}
