<?php
$host       = "127.0.0.1"; 
$port       = "1521";
$service_name = "XE";       
$username   = "FOUNDIT"; 
$password   = "FOUNDIT";   
$dsn = "oci:dbname=//{$host}:{$port}/{$service_name};charset=UTF8";

try {
    $conn = new PDO($dsn, $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi Database Oracle Gagal: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>