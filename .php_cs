<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        'empty_return' => false,
        'blankline_after_open_tag' => false,
        'ordered_imports' => true,
        'phpdoc_no_empty_return' => false,
        'array_syntax' => false,
    ))
    ->setFinder($finder)
    ;
