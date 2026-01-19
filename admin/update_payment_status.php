<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

// Pastikan method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Validasi status
    $allowedStatuses = ['pending', 'confirmed', 'failed'];
    if (!in_array($status, $allowedStatuses)) {
        redirect("payments.php");
        exit;
    }

    // Update database
    $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    // Redirect kembali
    redirect("payments.php");
} else {
    redirect("payments.php");
}
