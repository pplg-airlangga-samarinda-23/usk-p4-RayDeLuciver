<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('/auth/login.php');
    }
}

function require_admin(): void {
    require_login();
    $u = current_user();
    if (($u['role'] ?? '') !== 'Admin') {
        set_flash('error', 'Halaman ini khusus Admin.');
        redirect('/user/katalog.php');
    }
}

