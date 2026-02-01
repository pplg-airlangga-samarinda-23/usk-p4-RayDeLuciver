<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT * FROM buku';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE judul LIKE ? OR penulis LIKE ? OR kategori LIKE ? OR isbn LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY id_buku DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Admin: Data Buku</h3>
<p><a href="<?= e(base_url('/admin/buku_form.php')) ?>">Tambah Buku</a></p>

<form method="get">
  Cari: <input name="q" value="<?= e($q) ?>" placeholder="judul / penulis / kategori / ISBN">
  <button type="submit">Cari</button>
  <?php if ($q !== ''): ?>
    <a href="<?= e(base_url('/admin/buku.php')) ?>">Reset</a>
  <?php endif; ?>
</form>

<br>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>ID</th>
    <th>Judul</th>
    <th>Penulis</th>
    <th>Kategori</th>
    <th>Jumlah</th>
    <th>Tersedia</th>
    <th>Aksi</th>
  </tr>
  <?php if (!$books): ?>
    <tr><td colspan="7">Tidak ada data.</td></tr>
  <?php endif; ?>
  <?php foreach ($books as $b): ?>
    <tr>
      <td><?= (int)$b['id_buku'] ?></td>
      <td><?= e((string)$b['judul']) ?></td>
      <td><?= e((string)($b['penulis'] ?? $b['pengarang'] ?? '')) ?></td>
      <td><?= e((string)($b['kategori'] ?? '-')) ?></td>
      <td><?= (int)$b['jumlah_stok'] ?></td>
      <td><?= (int)$b['stok_tersedia'] ?></td>
      <td>
        <a href="<?= e(base_url('/admin/buku_form.php?id=' . (int)$b['id_buku'])) ?>">Edit</a>
        |
        <form style="display:inline" method="post" action="<?= e(base_url('/admin/buku_delete.php')) ?>" onsubmit="return confirm('Hapus buku ini?')">
          <input type="hidden" name="id_buku" value="<?= (int)$b['id_buku'] ?>">
          <button type="submit">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>

