<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_login();

$stmt = db()->prepare('
    SELECT p.*, b.judul, b.penulis
    FROM peminjaman p
    INNER JOIN buku b ON b.id_buku = p.id_buku
    WHERE p.id_pengguna = ?
    ORDER BY p.tanggal_peminjaman DESC
');
$stmt->execute([(int)current_user()['id_pengguna']]);
$rows = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Pinjaman Saya</h3>
<p><a href="<?= e(base_url('/user/katalog.php')) ?>">Kembali ke Katalog</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Buku</th>
    <th>Tanggal Pinjam</th>
    <th>Jatuh Tempo</th>
    <th>Status</th>
    <th>Denda</th>
    <th>Aksi</th>
  </tr>
  <?php if (!$rows): ?>
    <tr><td colspan="6">Belum ada peminjaman.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <?php $status = (string)$r['status_peminjaman']; ?>
    <tr>
      <td>
        <?= e((string)$r['judul']) ?><br>
        <small><?= e((string)($r['penulis'] ?? '')) ?></small>
      </td>
      <td><?= e((string)$r['tanggal_peminjaman']) ?></td>
      <td><?= e((string)$r['tanggal_jatuh_tempo']) ?></td>
      <td><?= e($status) ?></td>
      <td><?= number_format((float)$r['denda'], 0, ',', '.') ?></td>
      <td>
        <?php if ($status === 'Dipinjam'): ?>
          <form method="post" action="<?= e(base_url('/user/kembali.php')) ?>">
            <input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
            <button type="submit">Kembalikan</button>
          </form>
        <?php else: ?>
          -
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>

