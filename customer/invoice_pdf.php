<?php
require_once "../vendor/autoload.php"; 

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
include "../config/database.php";
include "../includes/functions.php";

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// ambil order
$stmt = $conn->prepare("
  SELECT o.*, u.name AS user_name, u.email AS user_email,
         a.detail AS address_detail, a.postal_code,
         p.name AS province_name, r.name AS regency_name
  FROM orders o
  JOIN users u ON u.id = o.user_id
  JOIN addresses a ON a.id = o.address_id
  JOIN regencies r ON r.id = a.regency_id
  JOIN provinces p ON p.id = a.province_id
  WHERE o.id = ? LIMIT 1
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order tidak ditemukan.");
}

// ambil items
$itStmt = $conn->prepare("
  SELECT oi.*, pr.name, pr.image
  FROM order_items oi 
  JOIN products pr ON pr.id = oi.product_id 
  WHERE oi.order_id = ?
");
$itStmt->bind_param("i", $order_id);
$itStmt->execute();
$items = $itStmt->get_result();
$itStmt->close();

// hitung total
$total = 0;
$rows = "";
while ($it = $items->fetch_assoc()) {
    $sub = $it['quantity'] * $it['price'];
    $total += $sub;

    $imgPath = "../assets/uploads/products/" . $it['image'];
    $imgTag = "";
    if (!empty($it['image']) && file_exists($imgPath)) {
        $imgTag = "<img src='$imgPath' style='width:50px; height:50px; object-fit:cover; border-radius:6px;'>";
    }

    $rows .= "
      <tr>
        <td style='padding:8px; display:flex; align-items:center; gap:8px;'>
            $imgTag <span>{$it['name']}</span>
        </td>
        <td style='padding:8px; text-align:right;'>".rupiah($it['price'])."</td>
        <td style='padding:8px; text-align:center;'>{$it['quantity']}</td>
        <td style='padding:8px; text-align:right;'>".rupiah($sub)."</td>
      </tr>";
}

$grand = (float)$order['total_amount'];
$shipping = $grand - $total;

// HTML template
$html = "
<!DOCTYPE html>
<html lang='id'>
<head>
<meta charset='UTF-8'>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
  h1,h2,h3 { margin:0; padding:0; }
  .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
  .title { font-size:18px; font-weight:bold; color:#2563eb; }
  .section { margin-bottom:20px; }
  .section h3 { font-size:14px; margin-bottom:6px; color:#374151; }
  table { width:100%; border-collapse: collapse; margin-top:10px; }
  th { background:#f3f4f6; padding:8px; text-align:left; font-size:12px; }
  td { border-top:1px solid #e5e7eb; font-size:12px; vertical-align:middle; }
  .totals { text-align:right; margin-top:15px; }
  .totals div { margin:4px 0; }
  .grand { font-weight:bold; font-size:14px; }
</style>
</head>
<body>

<div class='header'>
  <div class='title'>Invoice - ".($order['order_number'] ?? '#'.$order['id'])."</div>
  <div style='font-size:12px; color:#6b7280;'>Tanggal: {$order['created_at']}</div>
</div>

<div class='section'>
  <h3>Customer</h3>
  <div>{$order['user_name']} ({$order['user_email']})</div>
</div>

<div class='section'>
  <h3>Alamat Pengiriman</h3>
  <div>{$order['address_detail']}, {$order['regency_name']}, {$order['province_name']} - {$order['postal_code']}</div>
</div>

<div class='section'>
  <h3>Detail Pesanan</h3>
  <table>
    <thead>
      <tr>
        <th>Produk</th>
        <th style='text-align:right;'>Harga</th>
        <th style='text-align:center;'>Qty</th>
        <th style='text-align:right;'>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      $rows
    </tbody>
  </table>
</div>

<div class='totals'>
  <div>Total Produk: ".rupiah($total)."</div>
  <div>Ongkir: ".rupiah($shipping)."</div>
  <div class='grand'>Grand Total: ".rupiah($grand)."</div>
</div>

</body>
</html>
";

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("invoice-{$order['id']}.pdf", ["Attachment" => true]);
