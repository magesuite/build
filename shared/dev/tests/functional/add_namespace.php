<?php

if(isset($argv[1])) {
    $fileName = $argv[1];

    $existingConfiguration = json_decode(file_get_contents($fileName), true);

    if(!isset($existingConfiguration['autoload']['psr-4']['Creativestyle\\'])) {
        $existingConfiguration['autoload']['psr-4']['Creativestyle\\'] = 'tests/app/Creativestyle';

        $newConfigurationJson = json_encode($existingConfiguration, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        file_put_contents($fileName, $newConfigurationJson);

        echo 'New composer.json namespace configuration has been generated'.PHP_EOL;
    } else {
        echo 'Composer.json already has all required namespaces'.PHP_EOL;
    }
}
