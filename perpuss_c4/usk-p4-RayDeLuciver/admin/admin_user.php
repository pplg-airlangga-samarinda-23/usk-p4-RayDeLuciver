<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$stmt = db()->query('SELECT id, username FROM admin ORDER BY id DESC');
$admins = $stmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>

<h3>Admin: Data Administrator</h3>
<p><a href="<?= e(base_url('/admin/admin_user_form.php')) ?>">Tambah Admin</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>ID</th>
    <th>Username</th>
    <th>Aksi</th>
  </tr>

  <?php if (!$admins): ?>
    <tr><td colspan="3">Tidak ada data.</td></tr>
  <?php endif; ?>

  <?php foreach ($admins as $a): ?>
    <tr>
      <td><?= (int)$a['id'] ?></td>
      <td><?= e($a['username']) ?></td>
      <td>
        <a href="<?= e(base_url('/admin/admin_user_form.php?id=' . (int)$a['id'])) ?>">Edit</a>
        |
        <form method="post"
              action="<?= e(base_url('/admin/admin_user_delete.php')) ?>"
              style="display:inline"
              onsubmit="return confirm('Hapus admin ini?')">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button type="submit">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require __DIR__ . '/../partials/footer.php'; ?>
