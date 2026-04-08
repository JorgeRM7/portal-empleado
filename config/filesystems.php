<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'spaces' => [
            'driver' => 's3',
            'key' => env('SPACES_KEY'),
            'secret' => env('SPACES_SECRET'),
            'region' => env('SPACES_REGION', 'sfo3'),
            'bucket' => env('SPACES_BUCKET'),
            'url' => env('SPACES_URL'),
            'endpoint' => 'https://sfo3.digitaloceanspaces.com',
            'use_path_style_endpoint' => false,
            'throw' => true,
            'visibility' => 'public',
        ],
        'remote_sftp' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'), 
            'port' => (int) env('SFTP_PORT', 65002),

            'root' => env('SFTP_ROOT'),
            'timeout' => 30,
        ],
        'remote_sftp_inv' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST_INV'),
            'username' => env('SFTP_USERNAME_INV'),
            'password' => env('SFTP_PASSWORD_INV'), 
            'port' => (int) env('SFTP_PORT_INV', 65002),

            'root' => env('SFTP_ROOT_INV'),
            'timeout' => 30,
        ],
        'remote_sftp_public' => [
            'driver' => 'sftp',
            'host' => env('SFTP_HOST'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'), 
            'port' => (int) env('SFTP_PORT', 65002),

            'root' => env('SFTP_ROOT_PUBLIC'),
            'timeout' => 30,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
