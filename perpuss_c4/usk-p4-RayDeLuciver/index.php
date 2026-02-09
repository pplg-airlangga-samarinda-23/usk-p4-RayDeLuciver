<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

if (!isset($_SESSION['user'])) {
    redirect('/auth/login.php');
}

if (($_SESSION['user']['role'] ?? '') === 'Admin') {
    redirect('/admin/buku.php');
}

redirect('/user/katalog.php');

