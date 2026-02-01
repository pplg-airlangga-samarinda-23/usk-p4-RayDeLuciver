<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/katalog.php');
}

$idBuku = (int)($_POST['id_buku'] ?? 0);
if ($idBuku <= 0) {
    set_flash('error', 'Buku tidak valid.');
    redirect('/user/katalog.php');
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Kunci row buku agar stok konsisten
    $stmt = $pdo->prepare('SELECT id_buku, judul, stok_tersedia FROM buku WHERE id_buku = ? FOR UPDATE');
    $stmt->execute([$idBuku]);
    $b = $stmt->fetch();
    if (!$b) {
        throw new RuntimeException('Buku tidak ditemukan.');
    }
    if ((int)$b['stok_tersedia'] <= 0) {
        throw new RuntimeException('Stok buku habis.');
    }

    // Aturan jatuh tempo: 7 hari
    $jatuhTempo = (new DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');

    // Insert peminjaman
    $ins = $pdo->prepare('INSERT INTO peminjaman (id_pengguna, id_buku, tanggal_jatuh_tempo, status_peminjaman) VALUES (?, ?, ?, "Dipinjam")');
    $ins->execute([(int)current_user()['id_pengguna'], $idBuku, $jatuhTempo]);
    $idPeminjaman = (int)$pdo->lastInsertId();

    // Update stok secara eksplisit (lebih aman daripada trigger; trigger juga boleh ada)
    $upd = $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id_buku = ? AND stok_tersedia > 0');
    $upd->execute([$idBuku]);
    if ($upd->rowCount() !== 1) {
        throw new RuntimeException('Gagal mengurangi stok (kemungkinan stok sudah habis).');
    }

    // Log transaksi
    $log = $pdo->prepare('INSERT INTO transaksi (id_peminjaman, id_pengguna, id_buku, jenis_transaksi, keterangan, dibuat_oleh) VALUES (?, ?, ?, "Peminjaman", ?, NULL)');
    $log->execute([$idPeminjaman, (int)current_user()['id_pengguna'], $idBuku, 'Pinjam buku: ' . (string)$b['judul']]);

    $pdo->commit();
    set_flash('success', 'Berhasil meminjam buku. Jatuh tempo: ' . $jatuhTempo);
} catch (Throwable $e) {
    $pdo->rollBack();
    set_flash('error', $e->getMessage());
}

redirect('/user/pinjaman.php');

