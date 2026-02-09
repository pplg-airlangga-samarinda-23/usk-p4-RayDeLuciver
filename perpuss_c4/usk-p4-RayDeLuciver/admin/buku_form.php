<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$book = [
    'judul' => '',
    'penulis' => '',
    'pengarang' => '',
    'penerbit' => '',
    'tahun_terbit' => '',
    'isbn' => '',
    'jumlah_stok' => 0,
    'stok_tersedia' => 0,
    'kategori' => '',
    'deskripsi' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Buku tidak ditemukan.');
        redirect('/admin/buku.php');
    }
    $book = array_merge($book, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $penulis = trim((string)($_POST['penulis'] ?? ''));
    $pengarang = trim((string)($_POST['pengarang'] ?? ''));
    $penerbit = trim((string)($_POST['penerbit'] ?? ''));
    $tahun = trim((string)($_POST['tahun_terbit'] ?? ''));
    $isbn = trim((string)($_POST['isbn'] ?? ''));
    $kategori = trim((string)($_POST['kategori'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $jumlah = (int)($_POST['jumlah_stok'] ?? 0);

    if ($judul === '') {
        set_flash('error', 'Judul wajib diisi.');
        redirect($isEdit ? ('/admin/buku_form.php?id=' . $id) : '/admin/buku_form.php');
    }
    if ($jumlah < 0) $jumlah = 0;

    if (!$isEdit) {
        $stokTersedia = $jumlah;
        $ins = $pdo->prepare('
            INSERT INTO buku (judul, penulis, pengarang, penerbit, tahun_terbit, isbn, jumlah_stok, stok_tersedia, kategori, deskripsi)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $judul,
            $penulis ?: null,
            $pengarang ?: null,
            $penerbit ?: null,
            $tahun !== '' ? $tahun : null,
            $isbn !== '' ? $isbn : null,
            $jumlah,
            $stokTersedia,
            $kategori ?: null,
            $deskripsi ?: null,
        ]);
        set_flash('success', 'Buku berhasil ditambahkan.');
        redirect('/admin/buku.php');
    }

    // Edit: stok_tersedia ikut disesuaikan berdasarkan selisih jumlah_stok
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT jumlah_stok, stok_tersedia FROM buku WHERE id_buku = ? FOR UPDATE');
        $lock->execute([$id]);
        $cur = $lock->fetch();
        if (!$cur) throw new RuntimeException('Buku tidak ditemukan.');

        $curJumlah = (int)$cur['jumlah_stok'];
        $curTersedia = (int)$cur['stok_tersedia'];
        $delta = $jumlah - $curJumlah;
        $newTersedia = $curTersedia + $delta;
        if ($newTersedia < 0) $newTersedia = 0; // tidak boleh negatif

        $upd = $pdo->prepare('
            UPDATE buku
            SET judul = ?, penulis = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, isbn = ?, jumlah_stok = ?, stok_tersedia = ?, kategori = ?, deskripsi = ?
            WHERE id_buku = ?
        ');
        $upd->execute([
            $judul,
            $penulis ?: null,
            $pengarang ?: null,
            $penerbit ?: null,
            $tahun !== '' ? $tahun : null,
            $isbn !== '' ? $isbn : null,
            $jumlah,
            $newTersedia,
            $kategori ?: null,
            $deskripsi ?: null,
            $id,
        ]);

        $pdo->commit();
        set_flash('success', 'Buku berhasil diperbarui.');
        redirect('/admin/buku.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('error', $e->getMessage());
        redirect('/admin/buku_form.php?id=' . $id);
    }
}

require __DIR__ . '/../partials/header.php';
?>

<h3><?= $isEdit ? 'Edit Buku' : 'Tambah Buku' ?></h3>
<p><a href="<?= e(base_url('/admin/buku.php')) ?>">Kembali</a></p>

<form method="post">
  <p>
    Judul<br>
    <input name="judul" value="<?= e((string)$book['judul']) ?>" required>
  </p>
  <p>
    Kategori<br>
    <input name="kategori" value="<?= e((string)($book['kategori'] ?? '')) ?>">
  </p>
  <p>
    Penulis<br>
    <input name="penulis" value="<?= e((string)($book['penulis'] ?? '')) ?>">
  </p>
  <p>
    Pengarang<br>
    <input name="pengarang" value="<?= e((string)($book['pengarang'] ?? '')) ?>">
  </p>
  <p>
    Penerbit<br>
    <input name="penerbit" value="<?= e((string)($book['penerbit'] ?? '')) ?>">
  </p>
  <p>
    Tahun Terbit<br>
    <input name="tahun_terbit" value="<?= e((string)($book['tahun_terbit'] ?? '')) ?>" placeholder="2024">
  </p>
  <p>
    ISBN<br>
    <input name="isbn" value="<?= e((string)($book['isbn'] ?? '')) ?>">
  </p>
  <p>
    Jumlah Stok<br>
    <input type="number" min="0" name="jumlah_stok" value="<?= (int)($book['jumlah_stok'] ?? 0) ?>">
    <?php if ($isEdit): ?>
      <br><small>Stok tersedia akan disesuaikan otomatis berdasarkan selisih jumlah.</small>
    <?php endif; ?>
  </p>
  <p>
    Deskripsi<br>
    <textarea name="deskripsi" rows="3" cols="60"><?= e((string)($book['deskripsi'] ?? '')) ?></textarea>
  </p>
  <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah' ?></button>
</form>

<?php require __DIR__ . '/../partials/footer.php'; ?>

