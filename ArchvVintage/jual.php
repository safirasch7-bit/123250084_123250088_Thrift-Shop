<?php
require 'koneksi.php';
wajib_login();

// Ambil ID user yang sedang login dari session
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id === 0) {
    header("Location: login.php");
    exit;
}

// Ambil notifikasi flash
$notif = $_SESSION['notif'] ?? "";
unset($_SESSION['notif']);

// 1. Hitung statistik KHUSUS milik user yang sedang login
$stmt_stat = $conn->prepare("
    SELECT 
        COUNT(*) AS total_produk,
        COALESCE(SUM(stok), 0) AS total_stok,
        SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS produk_habis
    FROM produk
    WHERE user_id = ?
");
$stmt_stat->bind_param("i", $user_id);
$stmt_stat->execute();
$stats = $stmt_stat->get_result()->fetch_assoc();
$stmt_stat->close();

// 2. Fitur Pencarian & Daftar Produk KHUSUS milik user yang sedang login
$keyword = trim($_GET['cari'] ?? '');
if ($keyword !== '') {
    $like = "%" . $keyword . "%";
    $stmt = $conn->prepare("
        SELECT p.*, u.username AS penjual 
        FROM produk p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? AND p.nama_barang LIKE ? 
        ORDER BY p.id DESC
    ");
    $stmt->bind_param("is", $user_id, $like);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, u.username AS penjual 
        FROM produk p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.id DESC
    ");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$produk_list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang Saya - ArchvVintage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4">
        <a href="index.php" class="navbar-brand mb-0 h1">ArchvVintage</a>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm">Lihat Katalog</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-0">Histori & Kelola Barang Saya</h2>
        <p class="text-muted">Masuk sebagai <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>

        <!-- 3 Kartu Statistik Milik Akun Ini -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 shadow-sm">
                    <div class="text-muted small">Total Produk Saya</div>
                    <div class="fs-3 fw-bold"><?= (int)$stats['total_produk'] ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 shadow-sm">
                    <div class="text-muted small">Total Stok Saya</div>
                    <div class="fs-3 fw-bold"><?= (int)$stats['total_stok'] ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 shadow-sm">
                    <div class="text-muted small">Produk Habis</div>
                    <div class="fs-3 fw-bold"><?= (int)($stats['produk_habis'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Notifikasi Sukses -->
        <?php if ($notif): ?>
            <div id="alertNotif" class="alert alert-success">
                <?= htmlspecialchars($notif) ?>
            </div>
        <?php endif; ?>

        <!-- Pencarian & Tombol Jual Barang -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="GET" class="d-flex gap-2">
                <input
                    type="text"
                    name="cari"
                    class="form-control"
                    placeholder="Cari barang saya..."
                    value="<?= htmlspecialchars($keyword) ?>"
                >
                <button type="submit" class="btn btn-outline-secondary">Cari</button>
                <?php if ($keyword !== ''): ?>
                    <a href="jual.php" class="btn btn-outline-danger">Reset</a>
                <?php endif; ?>
            </form>
            <a href="tambah.php" class="btn btn-primary">+ Jual Barang</a>
        </div>

        <!-- Tabel Katalog Produk Milik User -->
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>Nama Barang & Deskripsi</th>
                            <th>Kategori / Kondisi</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($produk_list->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <?= $keyword !== '' ? "Barang tidak ditemukan." : "Anda belum memiliki barang yang dijual." ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $produk_list->fetch_assoc()): ?>
                                <?php
                                $stok = (int)$row['stok'];
                                if ($stok === 0) {
                                    $badge = '<span class="badge bg-danger">Habis</span>';
                                } elseif ($stok <= 2) {
                                    $badge = '<span class="badge bg-warning text-dark">Menipis</span>';
                                } else {
                                    $badge = '<span class="badge bg-success">Tersedia</span>';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <img
                                            src="uploads/<?= htmlspecialchars($row['foto']) ?>"
                                            alt="Foto Produk"
                                            class="rounded border"
                                            style="width: 70px; height: 70px; object-fit: cover;"
                                        >
                                    </td>
                                    <td style="max-width: 280px;">
                                        <div class="fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                        <div class="small text-muted"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></div>
                                        <div class="small text-secondary mt-1">
                                            Penjual: <?= htmlspecialchars($row['penjual']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td>Rp <?= number_format((int)str_replace('.', '', $row['harga']), 0, ',', '.') ?></td>
                                    <td><?= $stok ?></td>
                                    <td><?= $badge ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a
                                                href="edit.php?id=<?= $row['id'] ?>"
                                                class="btn btn-outline-secondary btn-sm"
                                            >Edit</a>
                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus barang ini?');"
                                            >
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const alertBox = document.getElementById('alertNotif');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = "opacity 0.5s ease";
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 500);
            }, 4000);
        }
    </script>
</body>
</html>
