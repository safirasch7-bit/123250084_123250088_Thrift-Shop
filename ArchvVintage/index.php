<?php
require 'koneksi.php';

$is_logged_in = isset($_SESSION['login']) && $_SESSION['login'] === true;
$current_user_id = $is_logged_in ? (int)($_SESSION['user_id'] ?? 0) : 0;
$username = $is_logged_in ? ($_SESSION['username'] ?? '') : '';

$notif = $_SESSION['notif'] ?? '';
unset($_SESSION['notif']);

$keyword = trim($_GET['cari'] ?? '');
$category = trim($_GET['kategori'] ?? '');
$like = '%' . $keyword . '%';

$stmt = $conn->prepare("
    SELECT p.*, u.username AS penjual
    FROM produk p
    JOIN users u ON p.user_id = u.id
    WHERE (? = '' OR p.nama_barang LIKE ? OR p.kategori LIKE ? OR p.deskripsi LIKE ?)
      AND (? = '' OR p.kategori = ?)
    ORDER BY p.id DESC
");
$stmt->bind_param('ssssss', $keyword, $like, $like, $like, $category, $category);
$stmt->execute();
$produk_list = $stmt->get_result();

$categories = mysqli_query($conn, "
    SELECT DISTINCT kategori
    FROM produk
    WHERE kategori <> ''
    ORDER BY kategori
");
if (!$categories) {
    die('Gagal memuat kategori produk: ' . htmlspecialchars(mysqli_error($conn)));
}

$category_url = function ($value) use ($keyword) {
    $params = ['kategori' => $value];
    if ($keyword !== '') {
        $params['cari'] = $keyword;
    }
    return 'index.php?' . http_build_query($params);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArchvVintage — Temukan Barang Vintage Favoritmu</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #24211f;
            --muted: #77716c;
            --paper: #fffefa;
            --canvas: #f7f5f0;
            --line: #e9e5de;
            --accent: #b94f36;
            --accent-dark: #943d2a;
            --soft-accent: #f7e9e3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--canvas);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        .site-header {
            position: sticky;
            z-index: 5;
            top: 0;
            background: rgba(255, 254, 250, .96);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
        }

        .header-inner {
            width: min(1200px, calc(100% - 40px));
            min-height: 76px;
            margin: auto;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .brand-mark {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 12px;
            color: white;
            background: var(--accent);
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: 700;
        }

        .brand-name {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .header-search {
            display: flex;
            overflow: hidden;
            flex: 1;
            max-width: 570px;
            height: 44px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #f8f7f4;
        }

        .header-search input {
            width: 100%;
            min-width: 0;
            padding: 0 18px;
            border: 0;
            outline: 0;
            background: transparent;
        }

        .header-search button {
            width: 48px;
            flex: 0 0 48px;
            border: 0;
            color: white;
            background: var(--accent);
            cursor: pointer;
            font-size: 19px;
        }

        .header-search button:hover,
        .button-primary:hover {
            background: var(--accent-dark);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-left: auto;
            white-space: nowrap;
        }

        .nav-link {
            padding: 10px 12px;
            font-size: 14px;
            font-weight: 650;
        }

        .button {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .button-primary {
            color: white;
            background: var(--accent);
        }

        .button-outline {
            border-color: var(--line);
            background: white;
        }

        .button-outline:hover {
            border-color: #c9b9af;
            background: #fffaf7;
        }

        .page {
            width: min(1200px, calc(100% - 40px));
            margin: 28px auto 60px;
        }

        .hero {
            position: relative;
            display: flex;
            overflow: hidden;
            min-height: 240px;
            align-items: center;
            padding: 42px 48px;
            border-radius: 24px;
            background: #eee1d4;
        }

        .hero::after {
            position: absolute;
            top: -90px;
            right: 6%;
            width: 320px;
            height: 320px;
            border: 1px solid rgba(126, 78, 54, .17);
            border-radius: 50%;
            content: "";
            box-shadow: 0 0 0 28px rgba(255,255,255,.13), 0 0 0 58px rgba(255,255,255,.1);
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            max-width: 590px;
        }

        .eyebrow {
            color: var(--accent-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.7px;
            text-transform: uppercase;
        }

        .hero h1 {
            margin: 10px 0 12px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 5vw, 48px);
            line-height: 1.08;
            letter-spacing: -1.2px;
        }

        .hero p {
            max-width: 470px;
            margin: 0;
            color: #62534a;
            font-size: 15px;
            line-height: 1.65;
        }

        .hero-cta {
            margin-top: 21px;
        }

        .category-bar {
            display: flex;
            overflow-x: auto;
            align-items: center;
            gap: 9px;
            padding: 20px 0 24px;
            scrollbar-width: thin;
        }

        .chip {
            flex: 0 0 auto;
            padding: 9px 15px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: #514b45;
            background: var(--paper);
            font-size: 13px;
            font-weight: 650;
        }

        .chip:hover,
        .chip.active {
            border-color: var(--accent);
            color: var(--accent-dark);
            background: var(--soft-accent);
        }

        .notice {
            margin: 0 0 20px;
            padding: 13px 16px;
            border: 1px solid #cfe7d2;
            border-radius: 12px;
            color: #236331;
            background: #eff9f0;
            font-size: 14px;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            margin: 5px 0 18px;
        }

        .section-head h2 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            letter-spacing: -.5px;
        }

        .section-head p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .clear-filter {
            color: var(--accent-dark);
            font-size: 13px;
            font-weight: 700;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .product-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--paper);
            transition: transform .18s ease, box-shadow .18s ease;
            height: 450px;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 26px rgba(46, 37, 29, .08);
        }

        .product-photo {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            background: #eee9e1;
        }

        .product-photo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s ease;
        }

        .product-card:hover .product-photo img {
            transform: scale(1.035);
        }

        .stock-tag {
            position: absolute;
            top: 11px;
            left: 11px;
            padding: 6px 9px;
            border-radius: 999px;
            color: #45413d;
            background: rgba(255,254,250,.94);
            font-size: 11px;
            font-weight: 750;
        }

        .stock-tag.sold {
            color: #943d2a;
        }

        .product-info {
            padding: 14px;
        }

        .product-category {
            overflow: hidden;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .product-name {
            display: -webkit-box;
            overflow: hidden;
            min-height: 35px;
            margin: 6px 0 2px;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .product-description {
            display: -webkit-box;
            overflow: hidden;
            min-height: 10px;
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .price {
            color: var(--accent-dark);
            margin-top: 8px; 
            font-size: 17px;
            font-weight: 800;
        }

        .seller {
            overflow: hidden;
            margin-top: 9px;
            color: var(--muted);
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-actions {
            display: flex;
            gap: 7px;
            margin-top: 12px;
            padding-top: 11px;
            border-top: 1px solid var(--line);
        }

        .card-actions .button {
            min-height: 33px;
            padding: 0 11px;
            font-size: 12px;
        }

        .button-danger {
            border-color: #f0d7d1;
            color: #a63e2c;
            background: #fff8f6;
        }

        .empty-state {
            grid-column: 1 / -1;
            padding: 54px 20px;
            border: 1px dashed #d9d2c9;
            border-radius: 16px;
            color: var(--muted);
            background: var(--paper);
            text-align: center;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 7px;
            color: var(--ink);
            font-family: Georgia, serif;
            font-size: 21px;
        }

        footer {
            padding: 24px 20px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .header-inner {
                flex-wrap: wrap;
                gap: 10px 18px;
                padding: 13px 0;
            }

            .header-search {
                order: 3;
                flex-basis: 100%;
                max-width: none;
            }

            .nav-actions {
                margin-left: auto;
            }

            .product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .header-inner,
            .page {
                width: min(100% - 28px, 1200px);
            }

            .header-inner {
                gap: 8px;
            }

            .brand-name {
                font-size: 18px;
            }

            .brand-mark {
                width: 34px;
                height: 34px;
            }

            .nav-actions {
                gap: 2px;
            }

            .nav-link {
                padding: 8px;
                font-size: 12px;
            }

            .nav-actions .button {
                min-height: 35px;
                padding: 0 11px;
                font-size: 12px;
            }

            .page {
                margin-top: 16px;
            }

            .hero {
                min-height: 240px;
                padding: 28px 23px;
                border-radius: 18px;
            }

            .hero::after {
                right: -180px;
            }

            .hero p {
                font-size: 14px;
            }

            .category-bar {
                margin-right: -14px;
            }

            .section-head h2 {
                font-size: 24px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 11px;
            }

            .product-info {
                padding: 11px;
            }

            .product-name {
                font-size: 14px;
            }

            .price {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="index.php" aria-label="ArchvVintage beranda">
                <span class="brand-mark">A</span>
                <span class="brand-name">ArchvVintage</span>
            </a>
            <form class="header-search" method="GET" action="index.php" role="search">
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($category) ?>">
                <?php endif; ?>
                <input
                    type="search"
                    name="cari"
                    value="<?= htmlspecialchars($keyword) ?>"
                    placeholder="Cari pakaian, aksesori, dan barang vintage..."
                    aria-label="Cari produk"
                >
                <button type="submit" aria-label="Cari">⌕</button>
            </form>
            <nav class="nav-actions" aria-label="Navigasi akun">
                <?php if ($is_logged_in): ?>
                    <span class="nav-link">Hai, <?= htmlspecialchars($username) ?></span>
                    <a class="button button-outline" href="logout.php">Keluar</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="button button-outline" href="register.php">Register</a>
                <?php endif; ?>
                <a
                    class="button button-primary"
                    href="<?= $is_logged_in ? 'jual.php' : 'login.php' ?>"
                >＋ Jual</a>
            </nav>
        </div>
    </header>

    <main class="page">
        <section class="hero">
            <div class="hero-copy">
                <div class="eyebrow">Pakai lagi, cerita lagi</div>
                <h1>Temukan harta karun vintage berikutnya.</h1>
                <p>Jelajahi pilihan thrift unik dari komunitas ArchvVintage — setiap barang punya cerita, dan mungkin cocok untukmu.</p>
                <a class="button button-primary hero-cta" href="#produk">Jelajahi koleksi&nbsp; ↓</a>
            </div>
        </section>

        <nav class="category-bar" aria-label="Filter kategori">
            <a
                class="chip <?= $category === '' ? 'active' : '' ?>"
                href="<?= htmlspecialchars($category_url('')) ?>"
            >Semua barang</a>
            <?php while ($category_row = mysqli_fetch_assoc($categories)): ?>
                <a
                    class="chip <?= $category === $category_row['kategori'] ? 'active' : '' ?>"
                    href="<?= htmlspecialchars($category_url($category_row['kategori'])) ?>"
                >
                <?= htmlspecialchars($category_row['kategori']) ?>
                </a>
            <?php endwhile; ?>
        </nav>

        <?php if ($notif !== ''): ?>
            <div class="notice" id="alertNotif"><?= htmlspecialchars($notif) ?></div>
        <?php endif; ?>

        <section id="produk">
            <div class="section-head">
                <div>
                    <h2><?= $keyword !== '' ? 'Hasil pencarian' : 'Pilihan untukmu' ?></h2>
                    <p><?= $keyword !== '' ? 'Menampilkan barang yang cocok dengan pencarianmu.' : 'Barang thrift unik yang baru ditambahkan komunitas.' ?></p>
                </div>
                <?php if ($keyword !== '' || $category !== ''): ?>
                    <a class="clear-filter" href="index.php">Hapus filter</a>
                <?php endif; ?>
            </div>

            <div class="product-grid">
                <?php if ($produk_list->num_rows === 0): ?>
                    <div class="empty-state">
                        <strong><?= $keyword !== '' || $category !== '' ? 'Barang belum ditemukan' : 'Belum ada barang di sini' ?></strong>
                        Coba kata kunci lain atau kembali lagi nanti untuk melihat koleksi terbaru.
                    </div>
                <?php else: ?>
                    <?php while ($row = $produk_list->fetch_assoc()): ?>
                        <?php
                        $stok = (int)$row['stok'];
                        $is_owner = $is_logged_in && (int)$row['user_id'] === $current_user_id;
                        ?>
                        <article class="product-card">
                            <div class="product-photo">
                                <img
                                    src="uploads/<?= htmlspecialchars($row['foto']) ?>"
                                    alt="<?= htmlspecialchars($row['nama_barang']) ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='assets/logo.png';"
                                >
                                <span class="stock-tag <?= $stok === 0 ? 'sold' : '' ?>">
                                    <?= $stok === 0 ? 'Terjual' : 'Stok ' . $stok ?>
                                </span>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($row['kategori']) ?></div>
                                <h3 class="product-name"><?= htmlspecialchars($row['nama_barang']) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                <div class="price">Rp <?= number_format((int)$row['harga'], 0, ',', '.') ?></div>
                                <div class="seller">Dijual oleh <?= htmlspecialchars($row['penjual']) ?></div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>ArchvVintage — Temukan gaya unik, beri kehidupan baru pada barang vintage.</footer>
    <script>
        const alertBox = document.getElementById('alertNotif');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity .5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 4000);
        }
    </script>

</body>
</html>