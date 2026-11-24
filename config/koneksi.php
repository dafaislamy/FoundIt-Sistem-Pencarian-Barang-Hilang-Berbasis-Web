<?php
$host = "127.0.0.1";
$port = "1521";
$service_name = "XE";
$username = "FOUNDIT";
$password = "FOUNDIT";

$dsn = "oci:dbname=//{$host}:{$port}/{$service_name};charset=UTF8";

try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    try {
        $mysql_host = "127.0.0.1";
        $mysql_db   = "foundit_web";
        $mysql_user = "root";
        $mysql_pass = "";
        $conn = new PDO("mysql:host={$mysql_host};dbname={$mysql_db};charset=utf8mb4", $mysql_user, $mysql_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e2) {
        die("Koneksi Database Gagal: " . $e->getMessage() . " | " . $e2->getMessage());
    }
}

$koneksi = $conn;
$pdo = $conn;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>