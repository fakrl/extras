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
            'root' => storage_path('app/private'),
            // DB.2 fix: disk ini tanpa 'url' eksplisit -> default URI bawaan
            // Laravel juga '/storage' (sama persis dengan disk 'public' di bawah),
            // jadi kalau dua-duanya serve=>true bentrok fatal saat boot. Nonaktifkan
            // DI SINI SAJA pas STAGING_NO_SYMLINK=true (satu-satunya saat disk
            // 'public' ikut serve=>true) - aman karena tidak ada kode app yang
            // pernah memanggil Storage::disk('local')->url()/temporaryUrl()
            // (dicek: grep nol hit), semua akses local selalu lewat controller
            // ->response()/->download() langsung, bukan via route /storage/{path}
            // ini. Default (STAGING_NO_SYMLINK kosong/false) tetap serve=>true,
            // perilaku produksi/dev tidak berubah.
            'serve' => ! env('STAGING_NO_SYMLINK', false),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
            // DB.2: InfinityFree men-disable symlink(). STAGING_NO_SYMLINK=true
            // memakai fitur bawaan Laravel (route /storage/{path} yang baca
            // langsung dari disk) jadi storage:link tidak pernah perlu dijalankan.
            // Default false = perilaku produksi tidak berubah (tetap via symlink).
            // Disk ini yang tetap memegang URI '/storage' (lihat fix di disk
            // 'local' di atas) - poster_path & semua Storage::url()/asset('storage/...')
            // yang sudah ada tetap resolve ke sini tanpa perubahan.
            'serve' => (bool) env('STAGING_NO_SYMLINK', false),
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
