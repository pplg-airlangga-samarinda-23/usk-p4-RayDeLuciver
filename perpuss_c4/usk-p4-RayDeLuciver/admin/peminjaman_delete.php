<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/peminjaman.php');
}

$id = (int)($_POST['id_peminjaman'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID peminjaman tidak valid.');
    redirect('/admin/peminjaman.php');
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Ambil data peminjaman untuk update stok jika perlu
    $stmt = $pdo->prepare('SELECT id_buku, status_peminjaman FROM peminjaman WHERE id_peminjaman = ?');
    $stmt->execute([$id]);
    $peminjaman = $stmt->fetch();

    if (!$peminjaman) {
        throw new RuntimeException('Data peminjaman tidak ditemukan.');
    }

    // Jika status masih Dipinjam, kembalikan stok buku
    if ((string)$peminjaman['status_peminjaman'] === 'Dipinjam') {
        $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = ?')
            ->execute([(int)$peminjaman['id_buku']]);
    }

    // Hapus peminjaman (transaksi akan terhapus karena ON DELETE SET NULL atau bisa dihapus manual)
    $pdo->prepare('DELETE FROM peminjaman WHERE id_peminjaman = ?')
        ->execute([$id]);

    $pdo->commit();
    set_flash('success', 'Data peminjaman berhasil dihapus.');
} catch (Throwable $e) {
    $pdo->rollBack();
    set_flash('error', 'Gagal menghapus peminjaman: ' . $e->getMessage());
}

redirect('/admin/peminjaman.php');
