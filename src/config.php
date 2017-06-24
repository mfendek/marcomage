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
        ],
        'mongo' => [
            'server' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => 'arcomage',
            'port' => '27017',
            'replica_set' => '', // this setting is relevant only if single replica set is used
            'additional_hosts' => '', // format mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
            'qlog' => true,
            'entity' => [
                'aliases' => false,
            ],
        ],
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
    'upload_dir' => [
        'avatar' => 'img/avatars/',
        'concept' => 'img/concepts/',
    ],
    'external_links' => [
        'google_plus' => 'https://plus.google.com/+MArcomage',
        'facebook' => 'https://www.facebook.com/pages/MArcomage/182322255140456',
    ],

    // maintenance scripts access
    'scripts' => [
        'password' => '',
    ],

    // used to flush cached files like stylesheets and javascript files
    'client_cache_version' => '2017-01-09',
];
