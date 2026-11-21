<?php
require 'koneksi.php';
wajib_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data lama
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    header("Location: index.php");
    exit;
}

$error = "";
$nama_barang = $produk['nama_barang'];
$kategori    = $produk['kategori'];
$harga       = $produk['harga'];
$stok        = $produk['stok'];
$deskripsi   = $produk['deskripsi'];
$foto_lama   = $produk['foto'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori    = trim($_POST['kategori'] ?? '');
    $harga       = $_POST['harga'] ?? '';
    $stok        = $_POST['stok'] ?? '';
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $foto_simpan = $foto_lama;

    if ($nama_barang === '' || strlen($nama_barang) > 100) {
        $error = "Nama barang wajib diisi (maksimal 100 karakter).";
    } elseif ($kategori === '' || strlen($kategori) > 50) {
        $error = "Kategori wajib diisi (maksimal 50 karakter).";
    } elseif (!is_numeric($harga) || (int)$harga < 0) {
        $error = "Harga harus berupa angka mulai dari 0.";
    } elseif (filter_var($stok, FILTER_VALIDATE_INT) === false || (int)$stok < 0) {
        $error = "Stok harus berupa bilangan bulat mulai dari 0.";
    } elseif ($deskripsi === '') {
        $error = "Deskripsi produk wajib diisi.";
    } else {
        // Cek apakah pengguna mengunggah foto baru
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
            $ekstensi = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

            if (!in_array($ekstensi, $ekstensi_valid)) {
                $error = "Format foto baru harus JPG, JPEG, PNG, atau WEBP.";
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran foto baru maksimal 2 MB.";
            } else {
                $nama_file_baru = uniqid('thrift_', true) . '.' . $ekstensi;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_file_baru)) {
                    // Hapus foto lama jika ada
                    if ($foto_lama && file_exists('uploads/' . $foto_lama)) {
                        unlink('uploads/' . $foto_lama);
                    }
                    $foto_simpan = $nama_file_baru;
                } else {
                    $error = "Gagal mengunggah foto baru.";
                }
            }
        }

        if ($error === "") {
            $harga_int = (int)$harga;
            $stok_int  = (int)$stok;

            $stmt = $conn->prepare("UPDATE produk SET nama_barang = ?, kategori = ?, harga = ?, stok = ?, deskripsi = ?, foto = ? WHERE id = ?");
            $stmt->bind_param("ssiissi", $nama_barang, $kategori, $harga_int, $stok_int, $deskripsi, $foto_simpan, $id);
            
            if ($stmt->execute()) {
                $_SESSION['notif'] = "Data barang berhasil diperbarui.";
                header("Location: index.php");
                exit;
            } else {
                $error = "Gagal memperbarui data.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Barang - Thrift Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 550px;">
    <div class="card shadow-sm p-4">
        <div class="text-uppercase text-muted small fw-bold">Thrift Shop</div>
        <h3 class="mb-3">Edit Barang</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label d-block">Foto Saat Ini</label>
                <img src="uploads/<?= htmlspecialchars($foto_lama) ?>" alt="Foto Lama" class="rounded border mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">Kosongkan jika tidak ingin mengganti foto produk.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($nama_barang) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($kategori) ?>" required>
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
                <textarea name="deskripsi" rows="3" class="form-control" required><?= htmlspecialchars($deskripsi) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="jual.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
</body>
</html>
