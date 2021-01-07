<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'no_useless_return' => false,
        'blank_line_after_opening_tag' => false,
        'ordered_imports' => true,
        'phpdoc_no_empty_return' => false,
        'yoda_style' => false,
        'no_superfluous_phpdoc_tags' => false,
    ])
    ->setFinder($finder)
    ;
