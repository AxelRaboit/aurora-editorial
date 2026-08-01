<?php

declare(strict_types=1);

// Mirrors aurora-core's ruleset verbatim: the two packages were one codebase
// until the split and are still read side by side, so a divergence in style
// would be noise, not signal. Kept as a copy rather than loaded from
// vendor/axelraboit/aurora so CI can run without resolving that dependency.
$projectDir = getcwd() ?: __DIR__;

$finder = PhpCsFixer\Finder::create()
    ->in($projectDir)
    ->exclude([
        'vendor',
        'tools',
        '.github',
        'config',
        'assets',
        'var',
        'node_modules',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@Symfony' => true,
        '@PHP83Migration' => true,
        '@DoctrineAnnotation' => true,
        'declare_strict_types' => true,
        'mb_str_functions' => true,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'modernize_strpos' => true,
        'set_type_to_cast' => true,
        'array_push' => true,
        'modernize_types_casting' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'array_indentation' => true,
        'list_syntax' => true,
        'no_spaces_inside_parenthesis' => true,
        'return_to_yield_from' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'single_line_empty_body' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache');
