<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_login();

$pdo = db();
$userId = (int)current_user()['id_pengguna'];

// Ambil data user saat ini
$stmt = $pdo->prepare('SELECT * FROM pengguna WHERE id_pengguna = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'Data pengguna tidak ditemukan.');
    redirect('/user/katalog.php');
}

$data = [
    'username' => (string)$user['username'],
    'nama_lengkap' => (string)($user['nama_lengkap'] ?? ''),
    'email' => (string)($user['email'] ?? ''),
    'alamat' => (string)($user['alamat'] ?? ''),
    'nomor_telepon' => (string)($user['nomor_telepon'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $passwordLama = (string)($_POST['password_lama'] ?? '');
    $passwordBaru = (string)($_POST['password_baru'] ?? '');
    $passwordBaru2 = (string)($_POST['password_baru2'] ?? '');
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));

    if ($username === '') {
        set_flash('error', 'Username wajib diisi.');
        redirect('/user/profil.php');
    }

    if (strlen($username) < 4) {
        set_flash('error', 'Username minimal 4 karakter.');
        redirect('/user/profil.php');
    }

    // Cek jika username berubah dan sudah digunakan
    if ($username !== $data['username']) {
        $cek = $pdo->prepare('SELECT 1 FROM pengguna WHERE username = ? AND id_pengguna != ? LIMIT 1');
        $cek->execute([$username, $userId]);
        if ($cek->fetchColumn()) {
            set_flash('error', 'Username sudah digunakan oleh pengguna lain.');
            redirect('/user/profil.php');
        }
    }

    // Validasi password jika ingin ganti password
    $ubahPassword = false;
    if ($passwordBaru !== '' || $passwordBaru2 !== '') {
        if ($passwordLama === '') {
            set_flash('error', 'Password lama wajib diisi untuk mengganti password.');
            redirect('/user/profil.php');
        }

        // Verifikasi password lama
        $stored = (string)($user['password'] ?? '');
        $ok = false;

        // Kompatibel dengan data sample MD5, tapi register baru pakai password_hash()
        if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
            $ok = (md5($passwordLama) === strtolower($stored));
        } else {
            $ok = password_verify($passwordLama, $stored);
        }

        if (!$ok) {
            set_flash('error', 'Password lama salah.');
            redirect('/user/profil.php');
        }

        if ($passwordBaru !== $passwordBaru2) {
            set_flash('error', 'Password baru dan konfirmasi password tidak sama.');
            redirect('/user/profil.php');
        }

        if (strlen($passwordBaru) < 6) {
            set_flash('error', 'Password baru minimal 6 karakter.');
            redirect('/user/profil.php');
        }

        $ubahPassword = true;
    }

    $pdo->beginTransaction();
    try {
        if ($ubahPassword) {
            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('
                UPDATE pengguna
                SET username = ?, nama_lengkap = ?, password = ?
                WHERE id_pengguna = ?
            ');
            $upd->execute([$username, $nama ?: null, $hash, $userId]);
        } else {
            $upd = $pdo->prepare('
                UPDATE pengguna
                SET username = ?, nama_lengkap = ?
                WHERE id_pengguna = ?
            ');
            $upd->execute([$username, $nama ?: null, $userId]);
        }

        // Update session jika username berubah
        if ($username !== $data['username']) {
            $_SESSION['user']['username'] = $username;
        }
        if ($nama !== $data['nama_lengkap']) {
            $_SESSION['user']['nama_lengkap'] = $nama;
        }

        $pdo->commit();
        set_flash('success', 'Profil berhasil diperbarui.' . ($ubahPassword ? ' Password telah diubah.' : ''));
        redirect('/user/profil.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        redirect('/user/profil.php');
    }
}

require __DIR__ . '/../partials/header.php';
?>

<h3>Edit Profil</h3>
<p><a href="<?= e(base_url('/user/katalog.php')) ?>">Kembali ke Katalog</a></p>

<form method="post" autocomplete="off">
  <p>
    Username<br>
    <input name="username" value="<?= e($data['username']) ?>" required minlength="4">
  </p>
  <p>
    Nama Lengkap<br>
    <input name="nama_lengkap" value="<?= e($data['nama_lengkap']) ?>">
  </p>
  
  <hr>
  <h4>Ganti Password (opsional)</h4>
  <p><small>Kosongkan jika tidak ingin mengganti password</small></p>
  
  <p>
    Password Lama<br>
    <input type="password" name="password_lama" autocomplete="current-password">
    <br><small>Wajib diisi jika ingin mengganti password</small>
  </p>
  <p>
    Password Baru<br>
    <input type="password" name="password_baru" autocomplete="new-password" minlength="6">
  </p>
  <p>
    Konfirmasi Password Baru<br>
    <input type="password" name="password_baru2" autocomplete="new-password" minlength="6">
  </p>
  
  <button type="submit">Simpan Perubahan</button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>
