<?php
$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ;

return Symfony\CS\Config\Config::create()
    ->fixers(array('-empty_return', '-blankline_after_open_tag', 'ordered_use', '-phpdoc_no_empty_return'))
    ->finder($finder)
    ;
