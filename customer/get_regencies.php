<?php
// customer/get_regencies.php
header('Content-Type: application/json; charset=utf-8');
include "../config/database.php";

$province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : 0;
$out = [];

if ($province_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, postal_code FROM regencies WHERE province_id = ? ORDER BY name");
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
    $stmt->close();
}
echo json_encode($out);
