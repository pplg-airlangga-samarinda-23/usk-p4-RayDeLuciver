<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID peminjaman tidak valid.');
    redirect('/admin/peminjaman.php');
}

// Ambil data peminjaman dengan join
$stmt = $pdo->prepare('
    SELECT
        p.*,
        pg.username,
        pg.nama_lengkap,
        b.judul,
        b.penulis
    FROM peminjaman p
    INNER JOIN pengguna pg ON pg.id_pengguna = p.id_pengguna
    INNER JOIN buku b ON b.id_buku = p.id_buku
    WHERE p.id_peminjaman = ?
');
$stmt->execute([$id]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    set_flash('error', 'Data peminjaman tidak ditemukan.');
    redirect('/admin/peminjaman.php');
}

$data = [
    'tanggal_jatuh_tempo' => (string)$peminjaman['tanggal_jatuh_tempo'],
    'tanggal_pengembalian_aktual' => (string)($peminjaman['tanggal_pengembalian_aktual'] ?? ''),
    'status_peminjaman' => (string)$peminjaman['status_peminjaman'],
    'denda' => (float)$peminjaman['denda'],
    'keterangan' => (string)($peminjaman['keterangan'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggalJatuhTempo = trim((string)($_POST['tanggal_jatuh_tempo'] ?? ''));
    $tanggalPengembalian = trim((string)($_POST['tanggal_pengembalian_aktual'] ?? ''));
    $status = trim((string)($_POST['status_peminjaman'] ?? ''));
    $denda = (float)($_POST['denda'] ?? 0);
    $keterangan = trim((string)($_POST['keterangan'] ?? ''));

    if ($tanggalJatuhTempo === '') {
        set_flash('error', 'Tanggal jatuh tempo wajib diisi.');
        redirect('/admin/peminjaman_form.php?id=' . $id);
    }

    if (!in_array($status, ['Dipinjam', 'Dikembalikan', 'Terlambat', 'Hilang'], true)) {
        set_flash('error', 'Status tidak valid.');
        redirect('/admin/peminjaman_form.php?id=' . $id);
    }

    if ($denda < 0) $denda = 0;

    $pdo->beginTransaction();
    try {
        $oldStatus = (string)$peminjaman['status_peminjaman'];
        $oldPengembalian = (string)($peminjaman['tanggal_pengembalian_aktual'] ?? '');

        // Update peminjaman
        $upd = $pdo->prepare('
            UPDATE peminjaman
            SET tanggal_jatuh_tempo = ?,
                tanggal_pengembalian_aktual = ?,
                status_peminjaman = ?,
                denda = ?,
                keterangan = ?
            WHERE id_peminjaman = ?
        ');
        $upd->execute([
            $tanggalJatuhTempo,
            $tanggalPengembalian !== '' ? $tanggalPengembalian : null,
            $status,
            $denda,
            $keterangan !== '' ? $keterangan : null,
            $id,
        ]);

        // Jika status berubah dari Dipinjam ke Dikembalikan, update stok buku
        if ($oldStatus === 'Dipinjam' && $status === 'Dikembalikan' && $oldPengembalian === '') {
            $pdo->prepare('UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = ?')
                ->execute([(int)$peminjaman['id_buku']]);
        }
        // Jika status berubah dari Dikembalikan ke Dipinjam, kurangi stok
        elseif ($oldStatus === 'Dikembalikan' && $status === 'Dipinjam') {
            $pdo->prepare('UPDATE buku SET stok_tersedia = GREATEST(stok_tersedia - 1, 0) WHERE id_buku = ?')
                ->execute([(int)$peminjaman['id_buku']]);
        }

        $pdo->commit();
        set_flash('success', 'Data peminjaman berhasil diperbarui.');
        redirect('/admin/peminjaman.php?status=' . urlencode($status));
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('error', 'Gagal memperbarui: ' . $e->getMessage());
        redirect('/admin/peminjaman_form.php?id=' . $id);
    }
}

require __DIR__ . '/../partials/header.php';
?>

<h3>Edit Data Peminjaman</h3>
<p><a href="<?= e(base_url('/admin/peminjaman.php')) ?>">Kembali</a></p>

<p>
  <strong>Peminjam:</strong> <?= e($peminjaman['nama_lengkap'] ?: $peminjaman['username']) ?> (@<?= e($peminjaman['username']) ?>)<br>
  <strong>Buku:</strong> <?= e($peminjaman['judul']) ?> - <?= e($peminjaman['penulis'] ?? '') ?><br>
  <strong>Tanggal Pinjam:</strong> <?= e($peminjaman['tanggal_peminjaman']) ?>
</p>

<form method="post">
  <p>
    Tanggal Jatuh Tempo<br>
    <input type="date" name="tanggal_jatuh_tempo" value="<?= e($data['tanggal_jatuh_tempo']) ?>" required>
  </p>
  <p>
    Tanggal Pengembalian Aktual<br>
    <input type="datetime-local" name="tanggal_pengembalian_aktual" value="<?= e($data['tanggal_pengembalian_aktual'] ? date('Y-m-d\TH:i', strtotime($data['tanggal_pengembalian_aktual'])) : '') ?>">
    <br><small>Kosongkan jika belum dikembalikan</small>
  </p>
  <p>
    Status<br>
    <select name="status_peminjaman" required>
      <?php foreach (['Dipinjam', 'Dikembalikan', 'Terlambat', 'Hilang'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $data['status_peminjaman'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </p>
  <p>
    Denda<br>
    <input type="number" min="0" step="0.01" name="denda" value="<?= number_format($data['denda'], 2, '.', '') ?>" required>
  </p>
  <p>
    Keterangan<br>
    <textarea name="keterangan" rows="3" cols="60"><?= e($data['keterangan']) ?></textarea>
  </p>
  <button type="submit">Simpan Perubahan</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
