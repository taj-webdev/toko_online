<?php
// customer/get_postal.php
header('Content-Type: application/json; charset=utf-8');
include "../config/database.php";

$regency_id = isset($_GET['regency_id']) ? (int)$_GET['regency_id'] : 0;
$out = ['postal_code' => ''];
if ($regency_id > 0) {
    $stmt = $conn->prepare("SELECT postal_code FROM regencies WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $regency_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) $out['postal_code'] = $r['postal_code'];
    $stmt->close();
}
echo json_encode($out);
