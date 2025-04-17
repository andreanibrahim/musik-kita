<?php
session_start();
include '../config/db_connect.php';

// Fungsi umum
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Proses update status pesanan
if (isset($_POST['update_status'])) {
    $id_transaction = (int)$_POST['id_transaction'];
    $status = trim($_POST['status']);

    // Validasi status
    $allowed_statuses = ['pending', 'diproses', 'selesai', 'dibatalkan'];
    if (!in_array($status, $allowed_statuses)) {
        header("Location: manage_orders.php?error=Status tidak valid");
        exit();
    }

    $stmt = $conn->prepare("UPDATE online_transactions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id_transaction);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_orders.php?success=Status pesanan berhasil diperbarui");
    exit();
}

// Ambil daftar pesanan
$orders_query = "SELECT ot.*, p.nama AS nama_produk, td.jumlah, td.subtotal
                 FROM online_transactions ot
                 JOIN offline_transaction_details td ON ot.id = td.id_transaction
                 JOIN produk p ON td.id_produk = p.id_produk
                 ORDER BY ot.tanggal DESC";
$orders_result = $conn->query($orders_query);

// Ambil detail pesanan jika ada parameter view
$view_order = null;
if (isset($_GET['view'])) {
    $id_transaction = (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT ot.*, p.nama AS nama_produk, td.jumlah, td.subtotal
                            FROM online_transactions ot
                            JOIN offline_transaction_details td ON ot.id = td.id_transaction
                            JOIN produk p ON td.id_produk = p.id_produk
                            WHERE ot.id = ?");
    $stmt->bind_param("i", $id_transaction);
    $stmt->execute();
    $view_order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Kelola Pesanan</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    table,
    th,
    td {
        border: 1px solid #ddd;
    }

    th,
    td {
        padding: 0.5rem;
        text-align: left;
    }

    th {
        background-color: #f0f0f0;
    }

    .action-buttons a {
        margin-right: 0.5rem;
        text-decoration: none;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        background-color: #4CAF50;
        color: #fff;
    }

    .status-form {
        display: inline-block;
    }

    .status-form select {
        padding: 0.3rem;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    .status-form button {
        padding: 0.3rem 0.6rem;
        background-color: #d32f2f;
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">MusikKita</a></h1>
            <nav>
                <a href="../index.php">Beranda</a>
                <a href="../order_status.php">Cek Pesanan</a>
                <a href="manage_products.php">Kelola Produk</a>
                <a href="manage_orders.php">Kelola Pesanan</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Kelola Pesanan</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="message" style="background: #e6ffe6; color: #2e7d32; border: 1px solid #2e7d32;">
            <?php echo sanitizeInput($_GET['success']); ?>
        </div>
        <?php elseif (isset($_GET['error'])): ?>
        <div class="message error">
            <?php echo sanitizeInput($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Detail Pesanan -->
        <?php if ($view_order): ?>
        <div class="checkout-box" style="margin-top: 1rem;">
            <h3>Detail Pesanan</h3>
            <p><strong>Kode Pesanan:</strong> <?php echo sanitizeInput($view_order['order_code']); ?></p>
            <p><strong>Nama Pembeli:</strong> <?php echo sanitizeInput($view_order['nama_pembeli']); ?></p>
            <p><strong>Email:</strong> <?php echo sanitizeInput($view_order['email_pembeli']); ?></p>
            <p><strong>Alamat:</strong> <?php echo nl2br(sanitizeInput($view_order['alamat_pembeli'])); ?></p>
            <p><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($view_order['tanggal'])); ?></p>
            <p><strong>Produk:</strong> <?php echo sanitizeInput($view_order['nama_produk']); ?>
                (<?php echo $view_order['jumlah']; ?> unit)</p>
            <p><strong>Total:</strong> <?php echo formatRupiah($view_order['total']); ?></p>
            <p><strong>Metode Pembayaran:</strong>
                <?php echo $view_order['payment_method'] === 'cod' ? 'COD (Bayar di Tempat)' : 'Transfer Bank'; ?></p>
            <?php if ($view_order['payment_method'] === 'transfer' && !empty($view_order['proof_of_payment'])): ?>
            <p><strong>Bukti Transaksi:</strong></p>
            <?php if (pathinfo($view_order['proof_of_payment'], PATHINFO_EXTENSION) === 'pdf'): ?>
            <a href="../<?php echo $view_order['proof_of_payment']; ?>" target="_blank">Lihat PDF Bukti Transaksi</a>
            <?php else: ?>
            <img src="../<?php echo $view_order['proof_of_payment']; ?>" alt="Bukti Transaksi"
                style="max-width: 300px; margin-top: 0.5rem;">
            <?php endif; ?>
            <?php endif; ?>
            <p><strong>Status:</strong> <?php echo ucfirst($view_order['status']); ?></p>
            <a href="manage_orders.php"
                style="display: inline-block; margin-top: 1rem; text-decoration: none; color: #d32f2f;">Kembali</a>
        </div>
        <?php else: ?>
        <!-- Daftar Pesanan -->
        <table>
            <thead>
                <tr>
                    <th>Kode Pesanan</th>
                    <th>Nama Pembeli</th>
                    <th>Total</th>
                    <th>Metode Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows === 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada pesanan.</td>
                </tr>
                <?php else: ?>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo sanitizeInput($order['order_code']); ?></td>
                    <td><?php echo sanitizeInput($order['nama_pembeli']); ?></td>
                    <td><?php echo formatRupiah($order['total']); ?></td>
                    <td><?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Transfer'; ?></td>
                    <td>
                        <form method="POST" class="status-form">
                            <input type="hidden" name="id_transaction" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                    Pending</option>
                                <option value="diproses"
                                    <?php echo $order['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="selesai" <?php echo $order['status'] === 'selesai' ? 'selected' : ''; ?>>
                                    Selesai</option>
                                <option value="dibatalkan"
                                    <?php echo $order['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan
                                </option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                    <td class="action-buttons">
                        <a href="manage_orders.php?view=<?php echo $order['id']; ?>">Lihat Detail</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 MusikKita. All Rights Reserved.</p>
        </div>
    </footer>

    <?php $conn->close(); ?>
</body>

</html>
