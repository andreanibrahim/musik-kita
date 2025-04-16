<?php
session_start();
include 'config/db_connect.php';

// Fungsi umum
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Ambil daftar produk
$products_query = "SELECT * FROM produk ORDER BY created_at DESC";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Beranda</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <header>
        <div class="container">
            <h1><a href="index.php">MusikKita</a></h1>
            <nav>
                <a href="index.php">Beranda</a>
                <a href="order_status.php">Cek Pesanan</a>
                <?php if (isset($_SESSION['role'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin/manage_products.php">Kelola Produk</a>
                <a href="admin/manage_orders.php">Kelola Pesanan</a>
                <?php elseif ($_SESSION['role'] === 'kasir'): ?>
                <a href="kasir/offline_transactions.php">Transaksi Offline</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
                <?php else: ?>
                <a href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Daftar Alat Musik</h2>

        <div class="product-grid">
            <?php if ($products_result->num_rows === 0): ?>
            <p style="text-align: center; color: #666;">Belum ada produk tersedia.</p>
            <?php else: ?>
            <?php while ($product = $products_result->fetch_assoc()): ?>
            <div class="product-card">
                <?php if (!empty($product['image'])): ?>
                <img src="<?php echo $product['image']; ?>" alt="<?php echo sanitizeInput($product['nama']); ?>">
                <?php else: ?>
                <div class="placeholder">Gambar Tidak Tersedia</div>
                <?php endif; ?>
                <div class="content">
                    <h3><?php echo sanitizeInput($product['nama']); ?></h3>
                    <p><?php echo sanitizeInput($product['kategori']); ?></p>
                    <p class="price"><?php echo formatRupiah($product['harga']); ?></p>
                    <p>Stok: <?php echo $product['stok']; ?></p>
                    <a href="product_detail.php?id=<?php echo $product['id_produk']; ?>">Beli</a>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 MusikKita. All Rights Reserved.</p>
        </div>
    </footer>

    <?php $conn->close(); ?>
</body>

</html>
