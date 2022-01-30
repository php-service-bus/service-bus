<?php

$config = new ServiceBus\CodeStyle\Config();
$config->getFinder()
  ->in(__DIR__ . '/src')
  ->in(__DIR__ . '/tests');

$cacheDir = '' !== (string) \getenv('TRAVIS') ? (string) \getenv('HOME') . '/.php-cs-fixer' : __DIR__;
$config->setCacheFile($cacheDir . '/.php_cs.cache');

return $config;
