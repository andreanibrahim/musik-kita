<?php
$host = 'localhost';
$dbname = 'musikkita_db';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}
?>
