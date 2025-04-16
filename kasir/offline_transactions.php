<?php
session_start();
include '../config/db_connect.php';

// Sertakan library FPDF
require '../fpdf/fpdf.php';

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
        $stmt = $conn->prepare("INSERT INTO offline_transactions (transaction_code, kasir_id, total, tanggal) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sdi", $transaction_code, $kasir_id, $subtotal);
        $stmt->execute();
        $stmt->close();
        $transaction_id = 1;

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
        $message = "Transaksi berhasil dengan kode: $transaction_code";
        $message_type = 'success';

        // Refresh riwayat transaksi
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("i", $kasir_id);
        $stmt->execute();
        $history_result = $stmt->get_result();
        $stmt->close();
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Proses unduh struk
if (isset($_GET['download_receipt'])) {
    $transaction_code = trim($_GET['download_receipt']);
    $stmt = $conn->prepare("SELECT ot.*, p.nama AS nama_produk, otd.jumlah, otd.subtotal, u.nama AS nama_kasir
                            FROM offline_transactions ot
                            JOIN offline_transaction_details otd ON ot.id = otd.id_transaction
                            JOIN produk p ON otd.id_produk = p.id_produk
                            JOIN users u ON ot.kasir_id = u.id
                            WHERE ot.transaction_code = ? AND ot.kasir_id = ?");
    $stmt->bind_param("si", $transaction_code, $kasir_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($transaction) {
        // Buat PDF menggunakan FPDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'MusikKita - Struk Pembelian', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Kode Transaksi: ' . $transaction['transaction_code'], 0, 1);
        $pdf->Cell(0, 10, 'Tanggal: ' . date('d M Y H:i', strtotime($transaction['tanggal'])), 0, 1);
        $pdf->Cell(0, 10, 'Kasir: ' . $transaction['nama_kasir'], 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(90, 10, 'Produk', 1, 0);
        $pdf->Cell(30, 10, 'Jumlah', 1, 0);
        $pdf->Cell(50, 10, 'Subtotal', 1, 1);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(90, 10, $transaction['nama_produk'], 1, 0);
        $pdf->Cell(30, 10, $transaction['jumlah'], 1, 0);
        $pdf->Cell(50, 10, formatRupiah($transaction['subtotal']), 1, 1);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Total: ' . formatRupiah($transaction['total']), 0, 1);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 10, 'Terima kasih telah berbelanja di MusikKita!', 0, 1, 'C');

        // Output PDF
        $pdf->Output('D', 'struk_' . $transaction['transaction_code'] . '.pdf');
        exit();
    } else {
        header("Location: offline_transactions.php?error=Transaksi tidak ditemukan");
        exit();
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
