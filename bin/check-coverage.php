<?php

declare(strict_types=1);

/*
 * Fails unless the JSON coverage report shows 100% line coverage.
 *
 * Usage: php bin/check-coverage.php [build/coverage.json]
 *
 * Branch and path coverage are printed for information only.
 */

$reportFile = $argv[1] ?? __DIR__.'/../build/coverage.json';
$json = @\file_get_contents($reportFile);

if (false === $json) {
    \fwrite(\STDERR, "Coverage report not found: {$reportFile}. Run `composer coverage` first.\n");
    exit(1);
}

/** @var array{summary: array<string, array{total: int, covered: int, percentage: float|int}>, files: array<string, array{lines: array{percentage: float|int, details: array<string, array{hits: int}>|list<mixed>}}>} $report */
$report = \json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

foreach (['lines', 'branches', 'paths', 'classes'] as $metric) {
    $summary = $report['summary'][$metric];
    \printf("%-9s %6.2f%% (%d/%d)\n", $metric, $summary['percentage'], $summary['covered'], $summary['total']);
}

$root = \dirname(__DIR__).'/';
$uncovered = [];
foreach ($report['files'] as $file => $data) {
    $lines = [];
    foreach ($data['lines']['details'] as $line => $detail) {
        if (\is_array($detail) && 0 === ($detail['hits'] ?? null)) {
            $lines[] = $line;
        }
    }
    if ([] !== $lines) {
        $uncovered[\str_replace($root, '', $file)] = $lines;
    }
}

if ([] === $uncovered) {
    echo "\nLine coverage is 100%.\n";
    exit(0);
}

\fwrite(\STDERR, "\nUncovered lines:\n");
foreach ($uncovered as $file => $lines) {
    \fwrite(\STDERR, \sprintf("  %s: %s\n", $file, \implode(', ', $lines)));
}
exit(1);
