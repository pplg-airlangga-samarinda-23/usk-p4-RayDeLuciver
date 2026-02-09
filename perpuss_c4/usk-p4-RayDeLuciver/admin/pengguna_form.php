<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM pengguna WHERE id_pengguna = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'Pengguna tidak ditemukan.');
    redirect('/admin/pengguna.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'Siswa';
    $status = $_POST['status_aktif'] ?? 'Aktif';
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        set_flash('error', 'Username wajib diisi.');
        redirect('/admin/pengguna_form.php?id=' . $id);
    }

    if ($password !== '') {
        $pdo->prepare('
            UPDATE pengguna
            SET username = ?, password = ?, role = ?, status_aktif = ?
            WHERE id_pengguna = ?
        ')->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $status,
            $id
        ]);
    } else {
        $pdo->prepare('
            UPDATE pengguna
            SET username = ?, role = ?, status_aktif = ?
            WHERE id_pengguna = ?
        ')->execute([$username, $role, $status, $id]);
    }

    set_flash('success', 'Data pengguna berhasil diperbarui.');
    redirect('/admin/pengguna.php');
}

require __DIR__ . '/../partials/header.php';
?>

<h3>Edit Pengguna</h3>
<p><a href="<?= e(base_url('/admin/pengguna.php')) ?>">Kembali</a></p>

<form method="post">
  <p>
    Username<br>
    <input name="username" value="<?= e($user['username']) ?>" required>
  </p>

  <p>
    Role<br>
    <select name="role">
      <option value="Siswa" <?= $user['role']==='Siswa'?'selected':'' ?>>Siswa</option>
      <option value="Admin" <?= $user['role']==='Admin'?'selected':'' ?>>Admin</option>
    </select>
  </p>

  <p>
    Status Akun<br>
    <select name="status_aktif">
      <option value="Aktif" <?= $user['status_aktif']==='Aktif'?'selected':'' ?>>Aktif</option>
      <option value="Tidak Aktif" <?= $user['status_aktif']==='Tidak Aktif'?'selected':'' ?>>Tidak Aktif</option>
    </select>
  </p>

  <p>
    Reset Password<br>
    <small>(kosongkan jika tidak diubah)</small><br>
    <input type="password" name="password">
  </p>

  <button type="submit">Simpan</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
