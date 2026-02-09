<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user'])) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        set_flash('error', 'Username dan password wajib diisi.');
        redirect('/auth/login.php');
    }

    $stmt = db()->prepare('SELECT * FROM pengguna WHERE username = ? AND status_aktif = "Aktif" LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('error', 'Login gagal. Username atau password salah.');
        redirect('/auth/login.php');
    }

    $stored = (string)($user['password'] ?? '');
    $ok = false;

    // Kompatibel dengan data sample MD5, tapi register baru pakai password_hash()
    if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
        $ok = (md5($password) === strtolower($stored));
    } else {
        $ok = password_verify($password, $stored);
    }

    if (!$ok) {
        set_flash('error', 'Login gagal. Username atau password salah.');
        redirect('/auth/login.php');
    }

    $_SESSION['user'] = [
        'id_pengguna' => (int)$user['id_pengguna'],
        'username' => (string)$user['username'],
        'role' => (string)$user['role'],
        'nama_lengkap' => (string)($user['nama_lengkap'] ?? ''),
    ];

    redirect('/');
}

require __DIR__ . '/../partials/header.php';
?>

<h3>Login</h3>
<form method="post" autocomplete="off">
  <p>
    Username<br>
    <input name="username" required>
  </p>
  <p>
    Password<br>
    <input type="password" name="password" required>
  </p>
  <button type="submit">Masuk</button>
</form>
<p>
  Belum punya akun? <a href="<?= e(base_url('/auth/register.php')) ?>">Register</a>
</p>

<?php require __DIR__ . '/../partials/footer.php'; ?>

