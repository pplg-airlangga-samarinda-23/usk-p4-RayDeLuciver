<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$status = trim((string)($_GET['status'] ?? 'Dipinjam'));
if ($status === '') $status = 'Dipinjam';

$stmt = db()->prepare('
    SELECT
        p.id_peminjaman,
        p.tanggal_peminjaman,
        p.tanggal_jatuh_tempo,
        p.tanggal_pengembalian_aktual,
        p.status_peminjaman,
        p.denda,
        pg.username,
        pg.nama_lengkap,
        b.judul,
        b.penulis
    FROM peminjaman p
    INNER JOIN pengguna pg ON pg.id_pengguna = p.id_pengguna
    INNER JOIN buku b ON b.id_buku = p.id_buku
    WHERE p.status_peminjaman = ?
    ORDER BY p.tanggal_peminjaman DESC
');
$stmt->execute([$status]);
$rows = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Admin: Data Peminjaman</h3>
<p>Melihat pengguna mana saja yang meminjam.</p>

<form method="get">
  Status:
  <select name="status">
    <?php foreach (['Dipinjam','Dikembalikan','Terlambat','Hilang'] as $s): ?>
      <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Tampilkan</button>
</form>

<br>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>ID</th>
    <th>Peminjam</th>
    <th>Buku</th>
    <th>Tgl Pinjam</th>
    <th>Jatuh Tempo</th>
    <th>Tgl Kembali</th>
    <th>Status</th>
    <th>Denda</th>
  </tr>
  <?php if (!$rows): ?>
    <tr><td colspan="8">Tidak ada data.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id_peminjaman'] ?></td>
      <td>
        <?= e((string)($r['nama_lengkap'] ?? '')) ?><br>
        <small>@<?= e((string)$r['username']) ?></small>
      </td>
      <td>
        <?= e((string)$r['judul']) ?><br>
        <small><?= e((string)($r['penulis'] ?? '')) ?></small>
      </td>
      <td><?= e((string)$r['tanggal_peminjaman']) ?></td>
      <td><?= e((string)$r['tanggal_jatuh_tempo']) ?></td>
      <td><?= e((string)($r['tanggal_pengembalian_aktual'] ?? '-')) ?></td>
      <td><?= e((string)$r['status_peminjaman']) ?></td>
      <td><?= number_format((float)$r['denda'], 0, ',', '.') ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>

