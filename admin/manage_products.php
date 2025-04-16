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
    header("Location: ../../login.php");
    exit();
}

// Proses tambah produk
if (isset($_POST['add_product'])) {
    $nama = trim($_POST['nama']);
    $kategori = trim($_POST['kategori']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);
    $image = null;

    // Validasi input
    $errors = [];
    if (empty($nama)) $errors[] = "Nama produk wajib diisi.";
    if (empty($kategori)) $errors[] = "Kategori wajib dipilih.";
    if ($harga <= 0) $errors[] = "Harga harus lebih dari 0.";
    if ($stok < 0) $errors[] = "Stok tidak boleh negatif.";
    if (empty($deskripsi)) $errors[] = "Deskripsi wajib diisi.";

    // Proses unggahan gambar jika ada
    if (!empty($_FILES['image']['name'])) {
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Gambar harus berformat JPG, JPEG, atau PNG.";
        }
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // Maks 5MB
            $errors[] = "Ukuran gambar maksimal 5MB.";
        }
    }

    if (empty($errors)) {
        if (!empty($_FILES['image']['name'])) {
            $image = 'assets/images/' . uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image);
        }

        $stmt = $conn->prepare("INSERT INTO produk (nama, kategori, harga, stok, deskripsi, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiis", $nama, $kategori, $harga, $stok, $deskripsi, $image);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_products.php?success=Produk berhasil ditambahkan");
        exit();
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Proses edit produk
if (isset($_POST['edit_product'])) {
    $id_produk = (int)$_POST['id_produk'];
    $nama = trim($_POST['nama']);
    $kategori = trim($_POST['kategori']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi input
    $errors = [];
    if (empty($nama)) $errors[] = "Nama produk wajib diisi.";
    if (empty($kategori)) $errors[] = "Kategori wajib dipilih.";
    if ($harga <= 0) $errors[] = "Harga harus lebih dari 0.";
    if ($stok < 0) $errors[] = "Stok tidak boleh negatif.";
    if (empty($deskripsi)) $errors[] = "Deskripsi wajib diisi.";

    if (empty($errors)) {
        // Ambil data produk untuk mendapatkan gambar lama
        $stmt = $conn->prepare("SELECT image FROM produk WHERE id_produk = ?");
        $stmt->bind_param("i", $id_produk);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_product = $result->fetch_assoc();
        $stmt->close();

        $image = $old_product['image'];
        if (!empty($_FILES['image']['name'])) {
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = "Gambar harus berformat JPG, JPEG, atau PNG.";
            }
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors[] = "Ukuran gambar maksimal 5MB.";
            }

            if (empty($errors)) {
                // Hapus gambar lama jika ada
                if ($image && file_exists('../' . $image)) {
                    unlink('../' . $image);
                }
                $image = 'assets/images/' . uniqid() . '.' . $file_extension;
                move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image);
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE produk SET nama = ?, kategori = ?, harga = ?, stok = ?, deskripsi = ?, image = ? WHERE id_produk = ?");
            $stmt->bind_param("ssiiisi", $nama, $kategori, $harga, $stok, $deskripsi, $image, $id_produk);
            $stmt->execute();
            $stmt->close();
            header("Location: manage_products.php?success=Produk berhasil diperbarui");
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

// Proses hapus produk
if (isset($_GET['delete'])) {
    $id_produk = (int)$_GET['delete'];

    // Ambil data produk untuk mendapatkan gambar
    $stmt = $conn->prepare("SELECT image FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    // Hapus gambar jika ada
    if ($product['image'] && file_exists('../' . $product['image'])) {
        unlink('../' . $product['image']);
    }

    // Hapus produk dari database
    $stmt = $conn->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_products.php?success=Produk berhasil dihapus");
    exit();
}

// Ambil daftar produk
$products_query = "SELECT * FROM produk ORDER BY created_at DESC";
$products_result = $conn->query($products_query);

// Ambil data produk untuk edit jika ada
$edit_product = null;
if (isset($_GET['edit'])) {
    $id_produk = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Kelola Produk</title>
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
    }

    .action-buttons a.edit {
        background-color: #4CAF50;
        color: #fff;
    }

    .action-buttons a.delete {
        background-color: #d32f2f;
        color: #fff;
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
        <h2>Kelola Produk</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="message" style="background: #e6ffe6; color: #2e7d32; border: 1px solid #2e7d32;">
            <?php echo sanitizeInput($_GET['success']); ?>
        </div>
        <?php elseif (isset($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Form Tambah/Edit Produk -->
        <div class="checkout-box" style="margin-top: 1rem;">
            <h3><?php echo $edit_product ? 'Edit Produk' : 'Tambah Produk Baru'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_product): ?>
                <input type="hidden" name="id_produk" value="<?php echo $edit_product['id_produk']; ?>">
                <input type="hidden" name="edit_product" value="1">
                <?php else: ?>
                <input type="hidden" name="add_product" value="1">
                <?php endif; ?>
                <label for="nama">Nama Produk</label>
                <input type="text" id="nama" name="nama"
                    value="<?php echo $edit_product ? sanitizeInput($edit_product['nama']) : ''; ?>" required>
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" required>
                    <option value="Gitar"
                        <?php echo $edit_product && $edit_product['kategori'] === 'Gitar' ? 'selected' : ''; ?>>Gitar
                    </option>
                    <option value="Drum"
                        <?php echo $edit_product && $edit_product['kategori'] === 'Drum' ? 'selected' : ''; ?>>Drum
                    </option>
                    <option value="Keyboard"
                        <?php echo $edit_product && $edit_product['kategori'] === 'Keyboard' ? 'selected' : ''; ?>>
                        Keyboard</option>
                    <option value="Aksesoris"
                        <?php echo $edit_product && $edit_product['kategori'] === 'Aksesoris' ? 'selected' : ''; ?>>
                        Aksesoris</option>
                </select>
                <label for="harga">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" min="1"
                    value="<?php echo $edit_product ? $edit_product['harga'] : ''; ?>" required>
                <label for="stok">Stok</label>
                <input type="number" id="stok" name="stok" min="0"
                    value="<?php echo $edit_product ? $edit_product['stok'] : ''; ?>" required>
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi"
                    required><?php echo $edit_product ? sanitizeInput($edit_product['deskripsi']) : ''; ?></textarea>
                <label for="image">Gambar Produk (opsional)</label>
                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png">
                <?php if ($edit_product && $edit_product['image']): ?>
                <p>Gambar saat ini: <img src="../<?php echo $edit_product['image']; ?>" alt="Gambar Produk"
                        style="max-width: 100px; margin-top: 0.5rem;"></p>
                <?php endif; ?>
                <button type="submit"
                    style="margin-top: 1rem;"><?php echo $edit_product ? 'Simpan Perubahan' : 'Tambah Produk'; ?></button>
                <?php if ($edit_product): ?>
                <a href="manage_products.php"
                    style="display: inline-block; margin-top: 1rem; text-decoration: none; color: #d32f2f;">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Daftar Produk -->
        <h3 style="margin-top: 2rem;">Daftar Produk</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products_result->num_rows === 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada produk.</td>
                </tr>
                <?php else: ?>
                <?php while ($product = $products_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo sanitizeInput($product['nama']); ?></td>
                    <td><?php echo sanitizeInput($product['kategori']); ?></td>
                    <td><?php echo formatRupiah($product['harga']); ?></td>
                    <td><?php echo $product['stok']; ?></td>
                    <td class="action-buttons">
                        <a href="manage_products.php?edit=<?php echo $product['id_produk']; ?>" class="edit">Edit</a>
                        <a href="manage_products.php?delete=<?php echo $product['id_produk']; ?>" class="delete"
                            onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
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

    <?php $conn->close(); ?>
</body>

</html>
