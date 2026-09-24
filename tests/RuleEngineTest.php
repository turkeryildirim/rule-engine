<?php

declare(strict_types=1);

namespace D6N\RuleEngine\Test;

use D6N\RuleEngine\RuleEngine;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    public function testVersionIsSemantic(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', RuleEngine::VERSION);
    }
}
