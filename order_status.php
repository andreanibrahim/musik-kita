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

$order = null;
if (isset($_GET['order_code'])) {
    $order_code = trim($_GET['order_code']);
    $stmt = $conn->prepare("SELECT ot.*, td.id_produk, td.jumlah, td.subtotal, p.nama AS nama_produk
                            FROM online_transactions ot
                            JOIN transaction_details td ON ot.id = td.id_transaction
                            JOIN produk p ON td.id_produk = p.id_produk
                            WHERE ot.order_code = ?");
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Cek Status Pesanan</title>
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
        <h2>Cek Status Pesanan</h2>

        <div class="checkout-box">
            <?php if ($order): ?>
            <h3>Detail Pesanan</h3>
            <p><strong>Kode Pesanan:</strong> <?php echo sanitizeInput($order['order_code']); ?></p>
            <p><strong>Nama Pembeli:</strong> <?php echo sanitizeInput($order['nama_pembeli']); ?></p>
            <p><strong>Email:</strong> <?php echo sanitizeInput($order['email_pembeli']); ?></p>
            <p><strong>Alamat:</strong> <?php echo nl2br(sanitizeInput($order['alamat_pembeli'])); ?></p>
            <p><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($order['tanggal'])); ?></p>
            <p><strong>Produk:</strong> <?php echo sanitizeInput($order['nama_produk']); ?>
                (<?php echo $order['jumlah']; ?> unit)</p>
            <p><strong>Total:</strong> <?php echo formatRupiah($order['total']); ?></p>
            <p><strong>Metode Pembayaran:</strong>
                <?php echo $order['payment_method'] === 'cod' ? 'COD (Bayar di Tempat)' : 'Transfer Bank'; ?></p>
            <?php if ($order['payment_method'] === 'transfer' && !empty($order['proof_of_payment'])): ?>
            <p><strong>Bukti Transaksi:</strong> Telah diunggah</p>
            <?php endif; ?>
            <p><strong>Status:</strong> <span
                    style="color: <?php echo $order['status'] === 'selesai' ? '#2e7d32' : ($order['status'] === 'dibatalkan' ? '#d32f2f' : '#666'); ?>; font-weight: bold;"><?php echo ucfirst($order['status']); ?></span>
            </p>
            <?php else: ?>
            <form method="GET">
                <label for="order_code">Masukkan Kode Pesanan</label>
                <input type="text" id="order_code" name="order_code"
                    value="<?php echo isset($_GET['order_code']) ? sanitizeInput($_GET['order_code']) : ''; ?>"
                    required>
                <button type="submit" style="margin-top: 1rem;">Cek Status</button>
            </form>
            <?php if (isset($_GET['order_code'])): ?>
            <div class="message error" style="margin-top: 1rem;">
                Kode pesanan tidak ditemukan.
            </div>
            <?php endif; ?>
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
