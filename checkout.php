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

// Pastikan data dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$id_produk = (int)$_POST['id_produk'];
$jumlah = (int)$_POST['jumlah'];

// Ambil data produk
$stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product || $jumlah <= 0 || $jumlah > $product['stok']) {
    header("Location: index.php");
    exit();
}

$subtotal = $product['harga'] * $jumlah;
$message = '';
$message_type = '';

// Proses checkout jika form disubmit
if (isset($_POST['submit_checkout'])) {
    $nama_pembeli = trim($_POST['nama_pembeli']);
    $email_pembeli = trim($_POST['email_pembeli']);
    $alamat_pembeli = trim($_POST['alamat_pembeli']);
    $payment_method = trim($_POST['payment_method']);
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];

    // Validasi input
    $errors = [];
    if (empty($nama_pembeli)) {
        $errors[] = "Nama pembeli wajib diisi.";
    }
    if (empty($email_pembeli) || !filter_var($email_pembeli, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email pembeli tidak valid.";
    }
    if (empty($alamat_pembeli)) {
        $errors[] = "Alamat pembeli wajib diisi.";
    }
    if (!in_array($payment_method, ['cod', 'transfer'])) {
        $errors[] = "Metode pembayaran tidak valid.";
    }

    // Validasi bukti transaksi jika metode pembayaran adalah Transfer
    $proof_of_payment = null;
    if ($payment_method === 'transfer') {
        if (empty($_FILES['proof_of_payment']['name'])) {
            $errors[] = "Bukti transaksi wajib diunggah untuk metode Transfer.";
        } else {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $file_extension = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = "File bukti transaksi harus berformat JPG, JPEG, PNG, atau PDF.";
            }
            if ($_FILES['proof_of_payment']['size'] > 5 * 1024 * 1024) { // Maks 5MB
                $errors[] = "Ukuran file bukti transaksi maksimal 5MB.";
            }
        }
    }

    // Validasi stok dan jumlah
    $stmt = $conn->prepare("SELECT stok FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $current_stok = $stmt->get_result()->fetch_assoc()['stok'];
    $stmt->close();

    if ($jumlah > $current_stok) {
        $errors[] = "Jumlah yang diminta melebihi stok tersedia.";
    }

    if (empty($errors)) {
        // Generate kode pesanan unik
        $order_code = 'ORD-' . strtoupper(uniqid());

        // Proses unggahan bukti transaksi jika ada
        if ($payment_method === 'transfer') {
            $upload_dir = 'uploads/';
            $file_name = 'proof_' . $order_code . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $upload_path)) {
                $proof_of_payment = $upload_path;
            } else {
                $errors[] = "Gagal mengunggah bukti transaksi.";
            }
        }

        if (empty($errors)) {
            // Simpan transaksi online
            $stmt = $conn->prepare("INSERT INTO online_transactions (order_code, nama_pembeli, email_pembeli, alamat_pembeli, total, payment_method, proof_of_payment) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $order_code, $nama_pembeli, $email_pembeli, $alamat_pembeli, $subtotal, $payment_method, $proof_of_payment);
            $stmt->execute();
            $transaction_id = $stmt->insert_id;
            $stmt->close();

            // Simpan detail transaksi
            $stmt = $conn->prepare("INSERT INTO transaction_details (id_transaction, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $transaction_id, $id_produk, $jumlah, $subtotal);
            $stmt->execute();
            $stmt->close();

            // Kurangi stok
            $new_stok = $current_stok - $jumlah;
            $stmt = $conn->prepare("UPDATE produk SET stok = ? WHERE id_produk = ?");
            $stmt->bind_param("ii", $new_stok, $id_produk);
            $stmt->execute();
            $stmt->close();

            // Redirect ke halaman konfirmasi
            header("Location: order_status.php?order_code=" . urlencode($order_code));
            exit();
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Checkout</title>
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
        <h2>Checkout</h2>

        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="checkout-box">
            <h3>Detail Pesanan</h3>
            <p><strong>Produk:</strong> <?php echo sanitizeInput($product['nama']); ?></p>
            <p><strong>Harga Satuan:</strong> <?php echo formatRupiah($product['harga']); ?></p>
            <p><strong>Jumlah:</strong> <?php echo $jumlah; ?></p>
            <p><strong>Subtotal:</strong> <?php echo formatRupiah($subtotal); ?></p>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_produk" value="<?php echo $id_produk; ?>">
                <input type="hidden" name="jumlah" value="<?php echo $jumlah; ?>">
                <label for="nama_pembeli">Nama Pembeli</label>
                <input type="text" id="nama_pembeli" name="nama_pembeli"
                    value="<?php echo isset($_POST['nama_pembeli']) ? sanitizeInput($_POST['nama_pembeli']) : ''; ?>"
                    required>
                <label for="email_pembeli">Email Pembeli</label>
                <input type="email" id="email_pembeli" name="email_pembeli"
                    value="<?php echo isset($_POST['email_pembeli']) ? sanitizeInput($_POST['email_pembeli']) : ''; ?>"
                    required>
                <label for="alamat_pembeli">Alamat Pengiriman</label>
                <textarea id="alamat_pembeli" name="alamat_pembeli"
                    required><?php echo isset($_POST['alamat_pembeli']) ? sanitizeInput($_POST['alamat_pembeli']) : ''; ?></textarea>

                <label for="payment_method">Metode Pembayaran</label>
                <select id="payment_method" name="payment_method" required onchange="showBankDetails()">
                    <option value="cod"
                        <?php echo isset($_POST['payment_method']) && $_POST['payment_method'] === 'cod' ? 'selected' : ''; ?>>
                        COD (Bayar di Tempat)</option>
                    <option value="transfer"
                        <?php echo isset($_POST['payment_method']) && $_POST['payment_method'] === 'transfer' ? 'selected' : ''; ?>>
                        Transfer Bank</option>
                </select>

                <div id="bank_details" style="display: none; margin-top: 1rem;">
                    <p>Nomor Rekening Bank: 1234-5678-9012 (Bank MusikKita)</p>
                </div>

                <div id="proof_upload" style="display: none; margin-top: 1rem;">
                    <label for="proof_of_payment">Unggah Bukti Transaksi</label>
                    <input type="file" id="proof_of_payment" name="proof_of_payment" accept=".jpg,.jpeg,.png,.pdf">
                </div>

                <button type="submit" name="submit_checkout" style="margin-top: 1rem;">Selesaikan Pesanan</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 MusikKita. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
    function showBankDetails() {
        const paymentMethod = document.getElementById('payment_method').value;
        const bankDetails = document.getElementById('bank_details');
        const proofUpload = document.getElementById('proof_upload');
        if (paymentMethod === 'transfer') {
            bankDetails.style.display = 'block';
            proofUpload.style.display = 'block';
        } else {
            bankDetails.style.display = 'none';
            proofUpload.style.display = 'none';
        }
    }

    // Panggil fungsi saat halaman dimuat untuk menangani kasus ketika halaman direfresh setelah error validasi
    document.addEventListener('DOMContentLoaded', showBankDetails);
    </script>

    <?php $conn->close(); ?>
</body>

</html>
