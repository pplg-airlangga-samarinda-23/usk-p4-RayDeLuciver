<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$data = [
    'username' => ''
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM admin WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Admin tidak ditemukan.');
        redirect('/admin/admin_user.php');
    }
    $data = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '') {
        set_flash('error', 'Username wajib diisi.');
        redirect($isEdit
            ? '/admin/admin_user_form.php?id=' . $id
            : '/admin/admin_user_form.php'
        );
    }

    if ($isEdit) {
        if ($password !== '') {
            $pdo->prepare(
                'UPDATE admin SET username = ?, password = ? WHERE id = ?'
            )->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $id
            ]);
        } else {
            $pdo->prepare(
                'UPDATE admin SET username = ? WHERE id = ?'
            )->execute([$username, $id]);
        }
        set_flash('success', 'Admin berhasil diperbarui.');
    } else {
        if ($password === '') {
            set_flash('error', 'Password wajib diisi.');
            redirect('/admin/admin_user_form.php');
        }
        $pdo->prepare(
            'INSERT INTO admin (username, password) VALUES (?, ?)'
        )->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT)
        ]);
        set_flash('success', 'Admin berhasil ditambahkan.');
    }

    redirect('/admin/admin_user.php');
}

require __DIR__ . '/../partials/header.php';
?>

<h3><?= $isEdit ? 'Edit Admin' : 'Tambah Admin' ?></h3>
<p><a href="<?= e(base_url('/admin/admin_user.php')) ?>">Kembali</a></p>

<form method="post">
  <p>
    Username<br>
    <input name="username" value="<?= e($data['username']) ?>" required>
  </p>

  <p>
    Password <?= $isEdit ? '(kosongkan jika tidak diubah)' : '' ?><br>
    <input type="password" name="password">
  </p>

  <button type="submit">Simpan</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
