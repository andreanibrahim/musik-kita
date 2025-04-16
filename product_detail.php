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

// Pastikan ID produk ada di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_produk = (int)$_GET['id'];

// Ambil data produk
$stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - <?php echo sanitizeInput($product['nama']); ?></title>
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
        <h2><?php echo sanitizeInput($product['nama']); ?></h2>

        <div class="product-detail">
            <div style="flex: 1; min-width: 250px;">
                <?php if (!empty($product['image'])): ?>
                <img src="<?php echo $product['image']; ?>" alt="<?php echo sanitizeInput($product['nama']); ?>">
                <?php else: ?>
                <div class="placeholder">Gambar Tidak Tersedia</div>
                <?php endif; ?>
            </div>
            <div style="flex: 1; min-width: 250px;">
                <p><strong>Kategori:</strong> <?php echo sanitizeInput($product['kategori']); ?></p>
                <p><strong>Harga:</strong> <span class="price"><?php echo formatRupiah($product['harga']); ?></span></p>
                <p><strong>Stok:</strong> <?php echo $product['stok']; ?> unit</p>
                <p><strong>Deskripsi:</strong></p>
                <p><?php echo nl2br(sanitizeInput($product['deskripsi'])); ?></p>

                <?php if ($product['stok'] > 0): ?>
                <form action="checkout.php" method="POST">
                    <input type="hidden" name="id_produk" value="<?php echo $product['id_produk']; ?>">
                    <label for="jumlah">Jumlah:</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" max="<?php echo $product['stok']; ?>"
                        value="1" required>
                    <button type="submit">Beli Sekarang</button>
                </form>
                <?php else: ?>
                <p style="color: #d32f2f; font-weight: bold;">Stok Habis</p>
                <?php endif; ?>
            </div>
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
