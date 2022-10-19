<?php

$isDockerEnv = (strpos(getcwd(), '/var/www/projects/') !== false);

if($isDockerEnv) {
    return [
        'db-host' => 'db',
        'db-user' => 'root',
        'db-password' => 'root',
        'db-name' => 'magento2_integration_tests',
        'db-prefix' => '',
        'es-hosts' => 'elasticsearch:9200',
        'backend-frontname' => 'backend',
        'base-url' => 'http://localhost/',
        'admin-user' => \Magento\TestFramework\Bootstrap::ADMIN_NAME,
        'admin-password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
        'admin-email' => \Magento\TestFramework\Bootstrap::ADMIN_EMAIL,
        'admin-firstname' => \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME,
        'admin-lastname' => \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME,
    ];
}

return [
    'db-host' => '127.0.0.1',
    'db-user' => 'root',
    'db-password' => 'vagrant',
    'db-name' => 'magento2_integration_tests',
    'db-prefix' => '',
    'es-hosts' => '127.0.0.1:9200',
    'backend-frontname' => 'backend',
    'base-url' => 'http://localhost/',
    'admin-user' => \Magento\TestFramework\Bootstrap::ADMIN_NAME,
    'admin-password' => \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD,
    'admin-email' => \Magento\TestFramework\Bootstrap::ADMIN_EMAIL,
    'admin-firstname' => \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME,
    'admin-lastname' => \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME,
];
