<?php

declare(strict_types=1);

// Coding standard for src/ and tests/, checked in CI via `composer cs`.
$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/bin'])
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony'                               => true,
        '@Symfony:risky'                         => true,
        'binary_operator_spaces'                 => ['operators' => ['=>' => 'align_single_space_minimal']],
        'declare_strict_types'                   => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'native_function_invocation'             => ['include' => ['@all'], 'scope' => 'all', 'strict' => true],
        'ordered_imports'                        => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'strict_param'                           => true,
        'no_superfluous_phpdoc_tags'             => false,
        'pow_to_exponentiation'                  => false,
        'increment_style'                        => false,
        'yoda_style'                             => false,
    ])
    ->setFinder($finder);
