<?php
$host = "localhost";
$user = "root"; // default XAMPP
$pass = "";     // kosong default
$db   = "toko_online";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
