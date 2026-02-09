<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$stmt = db()->query('
    SELECT
        id_pengguna,
        username,
        role,
        nama_lengkap,
        email,
        status_aktif,
        tanggal_daftar
    FROM pengguna
    ORDER BY id_pengguna DESC
');
$users = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Admin: Data Pengguna</h3>

<table border="1" cellpadding="6" cellspacing="0">
<tr>
  <th>ID</th>
  <th>Username</th>
  <th>Role</th>
  <th>Nama Lengkap</th>
  <th>Email</th>
  <th>Status</th>
  <th>Aksi</th>
</tr>

<?php if (!$users): ?>
<tr><td colspan="7">Belum ada pengguna.</td></tr>
<?php endif; ?>

<?php foreach ($users as $u): ?>
<tr>
  <td><?= (int)$u['id_pengguna'] ?></td>
  <td><?= e($u['username']) ?></td>
  <td><?= e($u['role']) ?></td>
  <td><?= e($u['nama_lengkap'] ?? '-') ?></td>
  <td><?= e($u['email'] ?? '-') ?></td>
  <td><?= e($u['status_aktif']) ?></td>
  <td>
    <a href="<?= e(base_url('/admin/pengguna_form.php?id=' . (int)$u['id_pengguna'])) ?>">
      Edit
    </a>
    |
    <form method="post"
          action="<?= e(base_url('/admin/pengguna_delete.php')) ?>"
          style="display:inline"
          onsubmit="return confirm('Yakin hapus pengguna ini?')">
      <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
      <button type="submit">Hapus</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
