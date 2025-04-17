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

// Ambil data untuk chart (hanya untuk admin)
$chart_data = [];
$sales_data = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Query untuk mendapatkan jumlah produk per kategori
    $chart_query = "SELECT kategori, COUNT(*) as jumlah FROM produk GROUP BY kategori";
    $chart_result = $conn->query($chart_query);
    
    while ($row = $chart_result->fetch_assoc()) {
        $chart_data[] = $row;
    }
    
    // Query untuk mendapatkan data penjualan offline
    $offline_query = "SELECT tanggal, SUM(total) as total_penjualan
                      FROM offline_transactions 
                      GROUP BY tanggal
                      ORDER BY tanggal DESC LIMIT 7";
    $offline_result = $conn->query($offline_query);
    
    $offline_sales = [];
    while ($row = $offline_result->fetch_assoc()) {
        $offline_sales[$row['tanggal']] = $row['total_penjualan'];
    }
    // var_dump($offline_sales);exit;
    // Query untuk mendapatkan data penjualan online
    $online_query = "SELECT tanggal, SUM(total) as total_penjualan 
                     FROM online_transactions 
                     WHERE status = 'selesai' 
                     GROUP BY tanggal
                     ORDER BY tanggal DESC LIMIT 7";
    $online_result = $conn->query($online_query);
    
    $online_sales = [];
    while ($row = $online_result->fetch_assoc()) {
        $online_sales[$row['tanggal']] = $row['total_penjualan'];
    }
    
    // Gabungkan data penjualan
    $all_dates = array_unique(array_merge(array_keys($offline_sales), array_keys($online_sales)));
    sort($all_dates);
    
    foreach ($all_dates as $date) {
        $sales_data[] = [
            'tanggal' => $date,
            'offline' => isset($offline_sales[$date]) ? $offline_sales[$date] : 0,
            'online' => isset($online_sales[$date]) ? $online_sales[$date] : 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikKita - Beranda</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Chart.js untuk admin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
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

    <main class="">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <!-- Dashboard chart untuk admin -->
        <div class="admin-dashboard">
            <h2>Dashboard Admin</h2>
            <div class="chart-container" style="position: relative; height:400px; width:100%; margin-bottom: 30px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

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

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Script untuk chart admin -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari PHP untuk chart produk
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        // Data dari PHP untuk chart penjualan
        const salesData = <?php echo json_encode($sales_data); ?>;
        
        // Persiapkan data untuk chart penjualan
        const salesLabels = salesData.map(item => item.tanggal);
        const offlineData = salesData.map(item => item.offline);
        const onlineData = salesData.map(item => item.online);
        
        // Buat chart penjualan
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [
                    {
                        label: 'Penjualan Offline',
                        data: offlineData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Penjualan Online',
                        data: onlineData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perbandingan Penjualan Online dan Offline (7 Hari Terakhir)'
                    },
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { 
                                        style: 'currency', 
                                        currency: 'IDR',
                                        minimumFractionDigits: 0
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('id-ID', { 
                                    style: 'currency', 
                                    currency: 'IDR',
                                    minimumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>

    <?php $conn->close(); ?>
</body>

</html>
