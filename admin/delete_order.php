<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

if (isset($_GET['id'])) {
    $orderId = (int) $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pesanan berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus pesanan.";
    }
}

redirect("orders.php");
