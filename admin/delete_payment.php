<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

// Pastikan ada id yang dikirim via GET
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Hapus data payment
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Balik ke halaman payments
    redirect("payments.php");
} else {
    redirect("payments.php");
}
