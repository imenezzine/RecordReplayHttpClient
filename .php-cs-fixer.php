<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src');

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    'use_arrow_functions' => true,
    'is_null' => true,
])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
