<?php
// Konfigurasi aplikasi
// Sesuaikan sesuai setting XAMPP kamu

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'perpustakaan_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Perpustakaan C4',
        // Ganti ini kalau aplikasi dipindah folder / domain lain
        'base_url' => '/perpuss_c4',
    ],
];

