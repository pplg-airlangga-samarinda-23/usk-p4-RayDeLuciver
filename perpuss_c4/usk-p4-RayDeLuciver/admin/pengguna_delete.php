<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/pengguna.php');
}

$id = (int)($_POST['id_pengguna'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID pengguna tidak valid.');
    redirect('/admin/pengguna.php');
}

// Hapus permanen
db()->prepare('DELETE FROM pengguna WHERE id_pengguna = ?')->execute([$id]);

set_flash('success', 'Pengguna berhasil dihapus.');
redirect('/admin/pengguna.php');
