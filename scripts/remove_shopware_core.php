<?php declare(strict_types=1);

$composerJsonPath = './composer.json';
$composerJson = json_decode(file_get_contents($composerJsonPath), true);

$composerJson['extra']['shopware-core-version'] = $composerJson['require']['shopware/core'];
unset($composerJson['require']['shopware/core']);

file_put_contents($composerJsonPath, json_encode($composerJson, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));