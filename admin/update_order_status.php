<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = (int) $_POST['order_id'];
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Status pesanan berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui status pesanan.";
    }
}

redirect("orders.php");
