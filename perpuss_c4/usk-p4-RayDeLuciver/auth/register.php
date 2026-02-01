<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user'])) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));
    $telp = trim((string)($_POST['nomor_telepon'] ?? ''));

    if ($username === '' || $password === '' || $nama === '') {
        set_flash('error', 'Username, password, dan nama lengkap wajib diisi.');
        redirect('/auth/register.php');
    }
    if (strlen($username) < 4) {
        set_flash('error', 'Username minimal 4 karakter.');
        redirect('/auth/register.php');
    }
    if (strlen($password) < 6) {
        set_flash('error', 'Password minimal 6 karakter.');
        redirect('/auth/register.php');
    }

    $stmt = db()->prepare('SELECT 1 FROM pengguna WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn()) {
        set_flash('error', 'Username sudah digunakan.');
        redirect('/auth/register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = db()->prepare('INSERT INTO pengguna (username, password, role, nama_lengkap, alamat, nomor_telepon, email) VALUES (?, ?, "Siswa", ?, ?, ?, ?)');
    $ins->execute([$username, $hash, $nama, $alamat ?: null, $telp ?: null, $email ?: null]);

    set_flash('success', 'Register berhasil. Silakan login.');
    redirect('/auth/login.php');
}

require __DIR__ . '/../partials/header.php';
?>

<h3>Register (Siswa)</h3>
<form method="post" autocomplete="off">
  <p>
    Username<br>
    <input name="username" required>
  </p>
  <p>
    Password<br>
    <input type="password" name="password" required>
  </p>
  <p>
    Nama Lengkap<br>
    <input name="nama_lengkap" required>
  </p>
  <p>
    Email (opsional)<br>
    <input type="email" name="email">
  </p>
  <p>
    Nomor Telepon (opsional)<br>
    <input name="nomor_telepon">
  </p>
  <p>
    Alamat (opsional)<br>
    <input name="alamat">
  </p>
  <button type="submit">Buat Akun</button>
</form>
<p>
  Sudah punya akun? <a href="<?= e(base_url('/auth/login.php')) ?>">Login</a>
</p>

<?php require __DIR__ . '/../partials/footer.php'; ?>

