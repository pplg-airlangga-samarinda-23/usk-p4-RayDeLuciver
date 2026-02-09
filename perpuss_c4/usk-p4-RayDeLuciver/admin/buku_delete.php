<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/buku.php');
}

$id = (int)($_POST['id_buku'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID buku tidak valid.');
    redirect('/admin/buku.php');
}

try {
    $stmt = db()->prepare('DELETE FROM buku WHERE id_buku = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Buku berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus buku: ' . $e->getMessage());
}

redirect('/admin/buku.php');

