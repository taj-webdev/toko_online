<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user']);
}

/**
 * Ambil role user yang login
 */
function userRole() {
    return $_SESSION['user']['role'] ?? null;
}

/**
 * Middleware: Wajib login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect("login.php");
    }
}

/**
 * Middleware: Wajib admin
 */
function requireAdmin() {
    requireLogin();
    if (userRole() !== 'admin') {
        redirect("index.php"); // bisa diarahkan ke halaman utama customer
    }
}

/**
 * Middleware: Wajib customer
 */
function requireCustomer() {
    requireLogin();
    if (userRole() !== 'customer') {
        redirect("admin/dashboard.php"); // misalnya kembalikan admin ke dashboard
    }
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Escape output (untuk keamanan XSS)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format Rupiah
 */
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

/**
 * Upload file sederhana (gambar produk, bukti transfer, dll)
 * @param $file $_FILES['name']
 * @param $targetDir folder target (misalnya "assets/uploads/")
 * @return string|false nama file jika sukses, false jika gagal
 */
function uploadFile($file, $targetDir = "assets/uploads/") {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = time() . "_" . basename($file['name']);
    $target = $targetDir . $filename;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return false;
    }
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }
    return false;
}
