<?php

declare(strict_types=1);

$composerJsonPath = './composer.json';
$composerJson = json_decode(file_get_contents($composerJsonPath), true);

$composerJson['require']['shopware/core'] = $composerJson['extra']['shopware-core-version'];
unset($composerJson['extra']['shopware-core-version']);

file_put_contents($composerJsonPath, json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
