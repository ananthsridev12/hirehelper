<?php

return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'hirehelper',
        'user'    => 'dbuser',
        'pass'    => 'dbpass',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'HireHelper',
        // Base path the app is served from, e.g. '' if served from domain root,
        // or '/hirehelper' if served from a subdirectory. No trailing slash.
        'base_path' => '',
        'env' => 'local', // local | production
    ],
];
