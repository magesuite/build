<?php

$rootDirectory = realpath(__DIR__ . '/../../../../creativestyle/');

$functionalTestsDirectories = glob($rootDirectory.'/vendor/*/*/Test/Functional');

foreach($functionalTestsDirectories as $directory) {
    echo 'Creating symlink for '.$directory;

    $pathParts = preg_match_all('/vendor\/(.*?)\/(.*?)\//si', $directory, $results, PREG_SET_ORDER);

    $vendorName = $results[0][1];
    $packageName = $results[0][2];

    $vendorName = ucwords($vendorName);
    $packageName = str_replace(' ', '', ucwords(str_replace('-',' ', $packageName)));

    $vendorDirectory = $rootDirectory . '/dev/tests/functional/tests/app/' . $vendorName;

    if(!file_exists($vendorDirectory)) {
        mkdir($vendorDirectory);
    }

    if(!file_exists($vendorDirectory.'/'.$packageName)) {
        mkdir($vendorDirectory.'/'.$packageName);
    }

    if(!file_exists($vendorDirectory.'/'.$packageName.'/Test')) {
        symlink($directory, $vendorDirectory.'/'.$packageName.'/Test');
    }
}
