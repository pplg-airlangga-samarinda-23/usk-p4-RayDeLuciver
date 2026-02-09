<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/admin_user.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID admin tidak valid.');
    redirect('/admin/admin_user.php');
}

// Cegah hapus akun sendiri
if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
    set_flash('error', 'Tidak bisa menghapus akun sendiri.');
    redirect('/admin/admin_user.php');
}

db()->prepare('DELETE FROM admin WHERE id = ?')->execute([$id]);

set_flash('success', 'Admin berhasil dihapus.');
redirect('/admin/admin_user.php');
