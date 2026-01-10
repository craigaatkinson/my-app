<?php

return [
    'default' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../src/app/SQLite/database.sqlite',
        'charset' => 'utf8',
        'persistent' => false,
    ],
    
    // You can add additional connections here
    'mysql' => [
        'app_env' => 'local',
        'app_debug' => true,
        'host' => 'localhost',
        // 'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'Atransport',
        'username' => 'root',
        'password' => 'newell29',
        'charset' => 'utf8mb4',
        'persistent' => false,
    ],
    'db' => [
        'app_env' => 'local',
        'app_debug' => true,
        'host' => 'localhost',
        // 'host' => '127.0.0.1',
        'port' => 3307,
        'database' => 'Atransport',
        'username' => 'root',
        'password' => 'newell29',
        'charset' => 'utf8mb4',
        'persistent' => false,
    ],
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../src/app/SQLite/database.sqlite',
        'charset' => 'utf8',
        'persistent' => false,
    ],
]; 

