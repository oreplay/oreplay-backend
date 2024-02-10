<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        // Translated custom rule
        'AppRest' => [
            'severity' => 'error',
        ],
    ])
    ->setFinder($finder);
