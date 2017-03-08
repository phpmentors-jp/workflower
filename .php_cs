<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        'no_useless_return' => false,
        'blank_line_after_opening_tag' => false,
        'ordered_imports' => true,
        'phpdoc_no_empty_return' => false,
    ))
    ->setFinder($finder)
    ;
