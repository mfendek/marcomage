<?php

/* -------------------------------- *
 * | MARCOMAGE CONFIGURATION FILE | *
 * -------------------------------- */

return [
    'db' => [
        'pdo' => [
            'server' => 'localhost',
            'username' => 'arcomage',
            'password' => '',
            'database' => 'arcomage',
            'port' => '',
            'qlog' => true,
        ]
    ],
    'logger' => [
        'error_writer' => [
            'type' => 'file',
        ],
        'debug_writer' => [
            'type' => 'file',
        ],
    ],
    'captcha' => [
        'enabled' => false,
        'public_key' => '',
        'private_key' => '',
    ],
    'jquery' => [
        'version' => '3.1.0',
        'ui_version' => '1.12.0',
    ],
    'bootstrap' => [
        'version' => '3.3.7',
    ],
    'upload_dir' => [
        'avatar' => 'img/avatars/',
        'concept' => 'img/concepts/',
    ],
    'external_links' => [
        'google_plus' => 'https://plus.google.com/101815655483915729081',
        'facebook' => 'https://www.facebook.com/pages/MArcomage/182322255140456',
    ],

    // maintenance scripts access
    'scripts' => [
        'password' => '',
    ],

    // used to flush cached files like stylesheets and javascript files
    'client_cache_version' => '2016-12-31',
];
