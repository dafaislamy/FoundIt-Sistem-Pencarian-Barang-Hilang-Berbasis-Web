
<?php
// includes/functions.php

/**
 * Menentukan lokasi gambar barang.
 * Jika gambar tidak ditemukan, gunakan no-image.svg.
 */
function foundit_image_path($storedPath, $fallback = null, $prefix = null) {

    if ($prefix === null) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $callerFile = $backtrace[0]['file'] ?? '';
        $isRoot = (dirname($callerFile) === dirname(__DIR__));
        $prefix = $isRoot ? '' : '../';
    }

    if ($fallback === null) {
        $fallback = $prefix . 'uploads/no-image.svg';
    }

    $storedPath = trim((string) ($storedPath ?? ''));

    if ($storedPath === '') {
        return $fallback;
    }

    // Mendukung URL gambar eksternal
    if (preg_match('~^https?://~i', $storedPath)) {
        return $storedPath;
    }

    // Menyesuaikan format alamat file
    $normalized = str_replace('\\', '/', $storedPath);
    $normalized = ltrim($normalized, '/');

    // Memastikan gambar berada di folder uploads
    if (strpos($normalized, 'uploads/') !== 0) {
        $normalized = 'uploads/' . basename($normalized);
    }

    // Memeriksa keberadaan file gambar
    $projectRoot = dirname(__DIR__);

    $absolutePath = $projectRoot . DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $normalized);

    if (is_file($absolutePath)) {
        return $prefix . $normalized;
    }

    return $fallback;
}


// Mengecek apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['id_user']);
}


// Mengecek apakah user adalah admin
function is_admin() {
    return isset($_SESSION['role']) &&
           $_SESSION['role'] === 'admin';
}


// Memastikan user sudah login
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../login.php");
        exit;
    }
}


// Memastikan akses admin
function require_admin() {
    if (!is_admin()) {
        header("Location: ../login.php");
        exit;
    }
}
?>
