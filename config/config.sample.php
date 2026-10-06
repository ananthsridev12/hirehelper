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
    'maps' => [
        // A Google Cloud API key with the Maps JavaScript API and Places
        // API enabled (needs billing turned on in that project). Leave
        // blank to keep working with plain-text addresses and browser/GPS
        // "use my location" -- autocomplete and the map preview simply
        // don't render without a key.
        'google_maps_key' => '',
    ],
    'push' => [
        // Leave both blank until a Firebase project exists for this app --
        // see app/Core/Notifier.php. The in-app notification feed works
        // today regardless; this only gates OS-level push delivery.
        'fcm_project_id' => '',
        'fcm_service_account_path' => '',
    ],
];
