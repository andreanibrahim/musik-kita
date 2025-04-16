<?php
session_start();
include 'config/db_connect.php';

// Fungsi umum
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$message = '';
$message_type = '';

// Jika sudah login, arahkan ke halaman masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/manage_products.php");
        exit();
    } elseif ($_SESSION['role'] === 'kasir') {
        header("Location: kasir/offline_transactions.php");
        exit();
    }
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validasi input
    $errors = [];
    if (empty($username)) {
        $errors[] = "Username wajib diisi.";
    }
    if (empty($password)) {
        $errors[] = "Password wajib diisi.";
    }

    if (empty($errors)) {
        // Cari pengguna di database
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil, simpan data ke sesi
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama'];

            // Arahkan berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: admin/manage_products.php");
                exit();
            } elseif ($user['role'] === 'kasir') {
                header("Location: kasir/offline_transactions.php");
                exit();
            }
        } else {
            $message = "Username atau password salah.";
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
    <title>MusikKita - Login</title>
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
        <h2>Login</h2>

        <div class="checkout-box" style="max-width: 400px; margin: 0 auto;">
            <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    value="<?php echo isset($_POST['username']) ? sanitizeInput($_POST['username']) : ''; ?>" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" style="margin-top: 1rem;">Login</button>
            </form>
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
