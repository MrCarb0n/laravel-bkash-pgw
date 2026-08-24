<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests'])
    ->name('*.php')
    ->notName('*.blade.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'break', 'continue', 'declare', 'try', 'catch', 'finally'],
        ],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => false,
        'fully_qualified_strict_types' => false,
        'function_declaration' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_alias_functions' => true,
        'no_extra_blank_lines' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_trailing_whitespace' => true,
        'no_unneeded_curly_braces' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_indent' => true,
        'phpdoc_line_span' => [
            'const' => 'multi',
            'method' => 'multi',
            'property' => 'multi',
        ],
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'phpdoc_to_return_type' => false,
        'protected_to_private' => false,
        'return_type_declaration' => ['space_before' => 'none'],
        'self_accessor' => true,
        'single_line_throw' => false,
        'standardize_not_equals' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false);