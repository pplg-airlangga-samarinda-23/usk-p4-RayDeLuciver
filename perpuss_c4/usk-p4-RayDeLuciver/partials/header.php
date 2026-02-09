<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
$config = require __DIR__ . '/../config/config.php';
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($config['app']['name']) ?></title>
</head>
<body>
<div>
  <strong><a href="<?= e(base_url('/')) ?>"><?= e($config['app']['name']) ?></a></strong>
  <hr>
  <?php if ($user): ?>
    Menu:
    <a href="<?= e(base_url('/user/katalog.php')) ?>">Katalog</a> |
    <a href="<?= e(base_url('/user/pinjaman.php')) ?>">Pinjaman Saya</a>
    | <a href="<?= e(base_url('/user/profil.php')) ?>">Profil</a>
    <?php if (($user['role'] ?? '') === 'Admin'): ?>
      | <a href="<?= e(base_url('/admin/buku.php')) ?>">Admin Buku</a>
      | <a href="<?= e(base_url('/admin/peminjaman.php')) ?>">Admin Peminjaman</a>
      | <a href="<?= e(base_url('/admin/admin_user.php')) ?>">Admin User</a>
      | <a href="<?= e(base_url('/admin/pengguna.php')) ?>">Admin Pengguna</a>
    <?php endif; ?>
    <br>
    Login sebagai: <?= e($user['nama_lengkap'] ?: $user['username']) ?> (<?= e($user['role']) ?>)
    | <a href="<?= e(base_url('/auth/logout.php')) ?>">Logout</a>
  <?php else: ?>
    <a href="<?= e(base_url('/auth/login.php')) ?>">Login</a> |
    <a href="<?= e(base_url('/auth/register.php')) ?>">Register</a>
  <?php endif; ?>
  <hr>
</div>

<?php if ($m = get_flash('success')): ?>
  <p><strong>OK:</strong> <?= e($m) ?></p>
  <hr>
<?php endif; ?>
<?php if ($m = get_flash('error')): ?>
  <p><strong>Error:</strong> <?= e($m) ?></p>
  <hr>
<?php endif; ?>

