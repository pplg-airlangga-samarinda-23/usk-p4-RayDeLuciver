<?php
declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';

date_default_timezone_set('Asia/Jakarta');

function base_url(string $path = ''): string {
    $config = require __DIR__ . '/config.php';
    $base = rtrim($config['app']['base_url'], '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function redirect(string $path): never {
    header('Location: ' . base_url($path));
    exit;
}

function set_flash(string $key, string $message): void {
    $_SESSION['_flash'][$key] = $message;
}

function get_flash(string $key): ?string {
    if (!isset($_SESSION['_flash'][$key])) return null;
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

