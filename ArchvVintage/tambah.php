<?php
require 'koneksi.php';
wajib_login();

$error = "";
$nama_barang = "";
$kategori = "";
$harga = "";
$stok = "1";
$deskripsi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori    = trim($_POST['kategori'] ?? '');
    $harga       = $_POST['harga'] ?? '';
    $stok        = $_POST['stok'] ?? '';
    $deskripsi   = trim($_POST['deskripsi'] ?? '');

    // Validasi input teks & angka
    if ($nama_barang === '' || strlen($nama_barang) > 100) {
        $error = "Nama barang wajib diisi (maksimal 100 karakter).";
    } elseif ($kategori === '' || strlen($kategori) > 50) {
        $error = "Kategori/kondisi wajib diisi (maksimal 50 karakter).";
    } elseif (!is_numeric($harga) || (int)$harga < 0) {
        $error = "Harga harus berupa angka mulai dari 0.";
    } elseif (filter_var($stok, FILTER_VALIDATE_INT) === false || (int)$stok < 0) {
        $error = "Stok harus berupa bilangan bulat mulai dari 0.";
    } elseif ($deskripsi === '') {
        $error = "Deskripsi produk wajib diisi.";
    } elseif (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $error = "Foto produk wajib diunggah.";
    } else {
        // Validasi ekstensi dan ukuran file foto
        $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
        $nama_file_asli = $_FILES['foto']['name'];
        $ukuran_file    = $_FILES['foto']['size'];
        $tmp_name       = $_FILES['foto']['tmp_name'];
        $ekstensi       = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));

        if (!in_array($ekstensi, $ekstensi_valid)) {
            $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
        } elseif ($ukuran_file > 2 * 1024 * 1024) {
            $error = "Ukuran foto maksimal 2 MB.";
        } else {
            // Buat nama file unik agar tidak tertimpa
            $nama_file_baru = uniqid('produk_', true) . '.' . $ekstensi;
            $tujuan = 'uploads/' . $nama_file_baru;

            if (move_uploaded_file($tmp_name, $tujuan)) {
                $user_id   = $_SESSION['user_id'];
                $harga_int = (int)$harga;
                $stok_int  = (int)$stok;

                $stmt = $conn->prepare("INSERT INTO produk (user_id, nama_barang, kategori, harga, stok, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issiiss", $user_id, $nama_barang, $kategori, $harga_int, $stok_int, $deskripsi, $nama_file_baru);
                
                if ($stmt->execute()) {
                    $_SESSION['notif'] = "Barang thrift berhasil ditambahkan ke katalog.";
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Gagal menyimpan data ke database.";
                }
                $stmt->close();
            } else {
                $error = "Gagal mengunggah foto ke server.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jual Barang - ArchvVintage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 550px;">
    <div class="card shadow-sm p-4">
        <div class="text-uppercase text-muted small fw-bold">Thrift Shop</div>
        <h3 class="mb-3">Jual Barang Baru</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Foto Produk (JPG/PNG/WEBP, maks 2MB)</label>
                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Jaket Denim Vintage 90s" value="<?= htmlspecialchars($nama_barang) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori & Keterangan Kondisi</label>
                <input type="text" name="kategori" class="form-control" placeholder="Contoh: Jaket - Like New 9/10" value="<?= htmlspecialchars($kategori) ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="harga" class="form-control" min="0" value="<?= htmlspecialchars($harga) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Stok</label>
                    <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($stok) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi Produk</label>
                <textarea name="deskripsi" rows="3" class="form-control" placeholder="Jelaskan ukuran (P x L), minus pemakaian, bahan, dll." required><?= htmlspecialchars($deskripsi) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Simpan & Jual</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
</body>
</html>
