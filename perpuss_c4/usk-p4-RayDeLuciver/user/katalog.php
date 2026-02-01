<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_login();

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT * FROM buku';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE judul LIKE ? OR penulis LIKE ? OR kategori LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY judul ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Katalog Buku</h3>
<p>Pilih buku untuk dipinjam (stok tersedia harus &gt; 0).</p>

<form method="get">
  Cari: <input name="q" value="<?= e($q) ?>" placeholder="judul / penulis / kategori">
  <button type="submit">Cari</button>
  <?php if ($q !== ''): ?>
    <a href="<?= e(base_url('/user/katalog.php')) ?>">Reset</a>
  <?php endif; ?>
</form>

<br>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Judul</th>
    <th>Penulis</th>
    <th>Kategori</th>
    <th>Stok</th>
    <th>Aksi</th>
  </tr>
  <?php if (!$books): ?>
    <tr><td colspan="5">Tidak ada data.</td></tr>
  <?php endif; ?>
  <?php foreach ($books as $b): ?>
    <tr>
      <td>
        <?= e((string)$b['judul']) ?><br>
        <small><?= e((string)($b['penerbit'] ?? '')) ?><?= $b['tahun_terbit'] ? ' - ' . e((string)$b['tahun_terbit']) : '' ?></small>
      </td>
      <td><?= e((string)($b['penulis'] ?? $b['pengarang'] ?? '')) ?></td>
      <td><?= e((string)($b['kategori'] ?? '-')) ?></td>
      <td><?= (int)$b['stok_tersedia'] ?></td>
      <td>
        <?php if ((int)$b['stok_tersedia'] > 0): ?>
          <form method="post" action="<?= e(base_url('/user/pinjam.php')) ?>">
            <input type="hidden" name="id_buku" value="<?= (int)$b['id_buku'] ?>">
            <button type="submit">Pinjam</button>
          </form>
        <?php else: ?>
          Stok habis
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>

