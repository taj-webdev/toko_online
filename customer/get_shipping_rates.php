<?php
// customer/get_shipping_rates.php
header('Content-Type: application/json; charset=utf-8');
include "../config/database.php";

$regency_id = isset($_GET['regency_id']) ? (int)$_GET['regency_id'] : 0;
$out = [];

if ($regency_id > 0) {
    $stmt = $conn->prepare("SELECT id, courier, cost, estimated_days FROM shipping_rates WHERE regency_id = ? ORDER BY courier");
    $stmt->bind_param("i", $regency_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        // cost cast numeric
        $r['cost'] = (float)$r['cost'];
        $out[] = $r;
    }
    $stmt->close();
}
echo json_encode($out);
