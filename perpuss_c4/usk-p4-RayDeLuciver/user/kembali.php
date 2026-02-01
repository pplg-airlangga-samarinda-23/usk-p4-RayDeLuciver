<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/pinjaman.php');
}

$idPeminjaman = (int)($_POST['id_peminjaman'] ?? 0);
if ($idPeminjaman <= 0) {
    set_flash('error', 'Peminjaman tidak valid.');
    redirect('/user/pinjaman.php');
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Kunci row peminjaman
    $stmt = $pdo->prepare('
        SELECT p.*, b.judul
        FROM peminjaman p
        INNER JOIN buku b ON b.id_buku = p.id_buku
        WHERE p.id_peminjaman = ? AND p.id_pengguna = ?
        FOR UPDATE
    ');
    $stmt->execute([$idPeminjaman, (int)current_user()['id_pengguna']]);
    $p = $stmt->fetch();
    if (!$p) {
        throw new RuntimeException('Data peminjaman tidak ditemukan.');
    }
    if ((string)$p['status_peminjaman'] !== 'Dipinjam') {
        throw new RuntimeException('Peminjaman ini sudah tidak berstatus Dipinjam.');
    }

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $upd = $pdo->prepare('UPDATE peminjaman SET status_peminjaman = "Dikembalikan", tanggal_pengembalian_aktual = ? WHERE id_peminjaman = ?');
    $upd->execute([$now, $idPeminjaman]);

    // Tambah stok
    $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = ?')
        ->execute([(int)$p['id_buku']]);

    // Hitung denda sederhana: Rp1000/hari lewat jatuh tempo
    $jatuhTempo = new DateTimeImmutable((string)$p['tanggal_jatuh_tempo']);
    $kembali = new DateTimeImmutable($now);
    $telatHari = (int)$jatuhTempo->diff($kembali)->format('%r%a');
    $denda = 0;
    if ($telatHari < 0) $telatHari = 0;
    if ($kembali > $jatuhTempo->setTime(23, 59, 59)) {
        $denda = $telatHari * 1000;
    }

    if ($denda > 0) {
        $pdo->prepare('UPDATE peminjaman SET denda = ? WHERE id_peminjaman = ?')->execute([$denda, $idPeminjaman]);
    }

    // Log transaksi
    $pdo->prepare('INSERT INTO transaksi (id_peminjaman, id_pengguna, id_buku, jenis_transaksi, jumlah, keterangan, dibuat_oleh) VALUES (?, ?, ?, "Pengembalian", ?, ?, NULL)')
        ->execute([
            $idPeminjaman,
            (int)current_user()['id_pengguna'],
            (int)$p['id_buku'],
            (float)$denda,
            $denda > 0 ? ('Kembali (telat). Denda: ' . $denda) : 'Kembali tepat waktu',
        ]);

    $pdo->commit();
    set_flash('success', 'Buku berhasil dikembalikan.' . ($denda > 0 ? (' Denda: ' . $denda) : ''));
} catch (Throwable $e) {
    $pdo->rollBack();
    set_flash('error', $e->getMessage());
}

redirect('/user/pinjaman.php');

