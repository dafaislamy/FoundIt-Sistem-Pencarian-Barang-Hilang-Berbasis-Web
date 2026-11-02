<?php // Developed by Fasbir Sabran
$host     = "localhost";
$username = "root";
$password = "";
$dbname   = "foundit_web";
$charset  = "utf8mb4";

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Inisialisasi koneksi PDO
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Alias $koneksi untuk kompatibilitas
    $koneksi = $pdo;

} catch (PDOException $e) {
    // Tangani kesalahan koneksi
    die("Koneksi database gagal: " . $e->getMessage());
}
?>