<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test\Fixtures;

class Invokable
{
    /**
     * @param mixed $value
     */
    public function __invoke($value = null): Fact
    {
        return new Fact($value);
    }
}
