<?php
// includes/functions.php

// Fungsi untuk mengecek apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['id_user']);
}

// Fungsi untuk mengecek apakah user yang login adalah admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fungsi untuk memaksa user harus login terlebih dahulu
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit;
    }
}

// Fungsi untuk memaksa harus akses admin
function require_admin() {
    if (!is_admin()) {
        header("Location: ../login.php");
        exit;
    }
}
?>