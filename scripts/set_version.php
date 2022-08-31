<?php

declare(strict_types=1);

var_dump($argv);

$version = $argv[1];

$composerJsonPath = './composer.json';
$composerJson = json_decode(file_get_contents($composerJsonPath), true);

$composerJson['version'] = $version;

file_put_contents($composerJsonPath, json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
