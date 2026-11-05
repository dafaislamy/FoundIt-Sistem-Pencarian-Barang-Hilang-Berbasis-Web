<?php
// includes/auth.php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/functions.php';

// Cek apakah user sudah login, jika belum arahkan ke halaman login
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit;
}
?>