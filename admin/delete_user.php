<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

if (!isset($_GET['id'])) {
    redirect("users.php");
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

redirect("users.php");
