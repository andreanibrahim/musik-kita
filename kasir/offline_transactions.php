<?php

use Fpdf\Fpdf;

session_start();
include '../config/db_connect.php';

// Sertakan library FPDF dari folder vendor
require_once('../vendor/autoload.php'); // Use Composer's autoloader

// Fungsi umum
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Cek apakah user sudah login dan memiliki role kasir
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../login.php");
    exit();
}

// Ambil daftar produk untuk dropdown
$products_query = "SELECT * FROM produk WHERE stok > 0 ORDER BY nama ASC";
$products_result = $conn->query($products_query);

// Ambil riwayat transaksi offline kasir
$kasir_id = $_SESSION['user_id'];
$history_query = "SELECT ot.*, p.nama AS nama_produk, otd.jumlah, otd.subtotal
                  FROM offline_transactions ot
                  JOIN offline_transaction_details otd ON ot.id = otd.id_transaction
                  JOIN produk p ON otd.id_produk = p.id_produk
                  WHERE ot.kasir_id = ?
                  ORDER BY ot.tanggal DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $kasir_id);
$stmt->execute();
$history_result = $stmt->get_result();
$stmt->close();

$message = '';
$message_type = '';
$last_transaction_code = '';

// Proses transaksi offline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];

    // Validasi input
    $errors = [];
    if ($id_produk <= 0) {
        $errors[] = "Produk wajib dipilih.";
    }
    if ($jumlah <= 0) {
        $errors[] = "Jumlah harus lebih dari 0.";
    }

    // Ambil data produk
    $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $errors[] = "Produk tidak ditemukan.";
    } elseif ($jumlah > $product['stok']) {
        $errors[] = "Jumlah yang diminta melebihi stok tersedia (" . $product['stok'] . ").";
    }

    if (empty($errors)) {
        $subtotal = $product['harga'] * $jumlah;
        $transaction_code = 'OFF-' . strtoupper(uniqid());
        $kasir_id = $_SESSION['user_id'];

        // Simpan transaksi offline
        $stmt = $conn->prepare("INSERT INTO offline_transactions (id_produk, kasir_id, total, transaction_code, jumlah, subtotal, tanggal) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiisii", $id_produk, $kasir_id, $subtotal, $transaction_code, $jumlah, $subtotal);
        $stmt->execute();
        $transaction_id = $stmt->insert_id;
        $stmt->close();

        // Simpan detail transaksi
        $stmt = $conn->prepare("INSERT INTO offline_transaction_details (id_transaction, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $transaction_id, $id_produk, $jumlah, $subtotal);
        $stmt->execute();
        $stmt->close();

        // Kurangi stok
        $new_stok = $product['stok'] - $jumlah;
        $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE id_produk = ?");
        $stmt->bind_param("ii", $new_stok, $id_produk);
        $stmt->execute();
        $stmt->close();

        $last_transaction_code = $transaction_code;
        
        // Store success message in session instead of variable
        $_SESSION['message'] = "Transaksi berhasil dengan kode: $transaction_code";
        $_SESSION['message_type'] = 'success';
        $_SESSION['last_transaction_code'] = $transaction_code;
        
        // Redirect to the same page to prevent form resubmission
        header("Location: offline_transactions.php");
        exit();
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    $last_transaction_code = isset($_SESSION['last_transaction_code']) ? $_SESSION['last_transaction_code'] : '';
    
    // Clear the session messages after retrieving them
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['last_transaction_code']);
}

// Proses unduh struk
if (isset($_GET['download_receipt'])) {
    $transaction_code = $_GET['download_receipt'];
    $stmt = $conn->prepare("SELECT ot.*, p.nama AS nama_produk, otd.jumlah, otd.subtotal
                            FROM offline_transactions ot
                            JOIN offline_transaction_details otd ON ot.id = otd.id_transaction
                            JOIN produk p ON otd.id_produk = p.id_produk
                            WHERE ot.transaction_code = ?
                            ORDER BY ot.tanggal DESC");
    $stmt->bind_param("s", $transaction_code);
    $stmt->execute();
    $transaction_result = $stmt->get_result();
    $transaction = $transaction_result->fetch_assoc();
    $stmt->close();

    if ($transaction) {
        $pdf = new Fpdf();
        $pdf->AddPage();
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        
        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Struk Transaksi', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Kode Transaksi: ' . $transaction['transaction_code'], 0, 1);
        $pdf->Cell(0, 8, 'Tanggal: ' . $transaction['tanggal'], 0, 1);
        $pdf->Cell(0, 8, 'Kasir: ' . $_SESSION['nama'], 0, 1);
        
        // Table header
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        
        // Define column widths
        $width_produk = 100;
        $width_jumlah = 30;
        $width_subtotal = 50;
        
        // Table headers
        $pdf->Cell($width_produk, 10, 'Produk', 1, 0, 'C');
        $pdf->Cell($width_jumlah, 10, 'Jumlah', 1, 0, 'C');
        $pdf->Cell($width_subtotal, 10, 'Subtotal', 1, 1, 'C');
        
        // Table data
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($width_produk, 10, $transaction['nama_produk'], 1, 0);
        $pdf->Cell($width_jumlah, 10, $transaction['jumlah'], 1, 0, 'C');
        $pdf->Cell($width_subtotal, 10, formatRupiah($transaction['subtotal']), 1, 1, 'R');
        
        // Total
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($width_produk + $width_jumlah, 10, 'Total:', 0, 0, 'R');
        $pdf->Cell($width_subtotal, 10, formatRupiah($transaction['total']), 0, 1, 'R');
        
        $pdf->Output('I', 'struk_transaksi.pdf', true);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Transaksi Offline</title>
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
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">MusikKita</a></h1>
            <nav>
                <a href="../index.php">Beranda</a>
                <a href="../order_status.php">Cek Pesanan</a>
                <a href="offline_transactions.php">Transaksi Offline</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Transaksi Offline</h2>

        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
            <?php if ($message_type === 'success' && $last_transaction_code): ?>
            <br>
            <a href="offline_transactions.php?download_receipt=<?php echo urlencode($last_transaction_code); ?>"
                style="color: #4CAF50; text-decoration: underline;">Unduh Struk</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="checkout-box" style="max-width: 600px; margin: 0 auto;">
            <h3>Tambah Transaksi</h3>
            <form method="POST" id="transaction_form">
                <label for="id_produk">Pilih Produk</label>
                <select id="id_produk" name="id_produk" required onchange="updatePrice()">
                    <option value="">-- Pilih Produk --</option>
                    <?php
                    // Reset pointer result set agar bisa digunakan lagi
                    $products_result->data_seek(0);
                    while ($product = $products_result->fetch_assoc()): ?>
                    <option value="<?php echo $product['id_produk']; ?>" data-harga="<?php echo $product['harga']; ?>"
                        data-stok="<?php echo $product['stok']; ?>">
                        <?php echo sanitizeInput($product['nama']); ?> (Stok: <?php echo $product['stok']; ?>, Harga:
                        <?php echo formatRupiah($product['harga']); ?>)
                    </option>
                    <?php endwhile; ?>
                </select>

                <label for="jumlah">Jumlah</label>
                <input type="number" id="jumlah" name="jumlah" min="1" value="1" required oninput="updatePrice()">

                <label for="subtotal">Subtotal</label>
                <input type="text" id="subtotal" value="Rp 0" readonly>

                <button type="submit" name="submit_transaction" style="margin-top: 1rem;">Selesaikan Transaksi</button>
            </form>
        </div>

        <!-- Riwayat Transaksi -->
        <h3 style="margin-top: 2rem;">Riwayat Transaksi</h3>
        <table>
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history_result->num_rows === 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada transaksi.</td>
                </tr>
                <?php else: ?>
                <?php while ($transaction = $history_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo sanitizeInput($transaction['transaction_code']); ?></td>
                    <td><?php echo sanitizeInput($transaction['nama_produk']); ?></td>
                    <td><?php echo $transaction['jumlah']; ?></td>
                    <td><?php echo formatRupiah($transaction['total']); ?></td>
                    <td><?php echo date('d M Y H:i', strtotime($transaction['tanggal'])); ?></td>
                    <td>
                        <a href="offline_transactions.php?download_receipt=<?php echo urlencode($transaction['transaction_code']); ?>"
                            style="color: #4CAF50; text-decoration: none;">Unduh Struk</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 MusikKita. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
    function updatePrice() {
        const productSelect = document.getElementById('id_produk');
        const jumlahInput = document.getElementById('jumlah');
        const subtotalInput = document.getElementById('subtotal');

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const harga = parseInt(selectedOption ? selectedOption.getAttribute('data-harga') : 0);
        const stok = parseInt(selectedOption ? selectedOption.getAttribute('data-stok') : 0);
        let jumlah = parseInt(jumlahInput.value);

        if (jumlah > stok) {
            jumlah = stok;
            jumlahInput.value = stok;
        }

        const subtotal = harga * jumlah;
        subtotalInput.value = 'Rp ' + subtotal.toLocaleString('id-ID');
    }

    // Panggil fungsi saat halaman dimuat untuk inisialisasi subtotal
    document.addEventListener('DOMContentLoaded', updatePrice);
    </script>

    <?php $conn->close(); ?>
</body>

</html>
