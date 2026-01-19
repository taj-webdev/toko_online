<?php
// customer/invoice.php
session_start();
include "../config/database.php";
include "../includes/functions.php";
include "../includes/header.php";

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo "<div class='p-6 text-red-600'>Order tidak ditemukan.</div>";
    include "../includes/footer.php";
    exit;
}

// ambil order + user + address + payment
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
    echo "<div class='p-6 text-red-600'>Order tidak ditemukan.</div>";
    include "../includes/footer.php";
    exit;
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

// ambil payment
$payStmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
$payStmt->bind_param("i", $order_id);
$payStmt->execute();
$payment = $payStmt->get_result()->fetch_assoc();
$payStmt->close();
?>

<div class="container mx-auto p-6 max-w-5xl">
  <div class="bg-white p-6 rounded-2xl shadow-lg">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b pb-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <!-- icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Invoice - <?= e($order['order_number'] ?? ('#'.$order['id'])) ?>
        </h1>
        <p class="text-sm text-gray-500">Tanggal: <?= e($order['created_at']) ?></p>
      </div>
      <div class="mt-3 md:mt-0">
        <span class="px-3 py-1 text-sm rounded-full 
          <?= $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
          Status: <?= ucfirst($order['status']) ?>
        </span>
      </div>
    </div>

    <!-- Info Customer & Alamat -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="p-4 border rounded-lg bg-gray-50">
        <h3 class="font-semibold flex items-center gap-2 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.485 0 4.774.732 6.879 1.975M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Data Customer
        </h3>
        <div><?= e($order['user_name']) ?></div>
        <div class="text-sm text-gray-600"><?= e($order['user_email']) ?></div>
      </div>
      <div class="p-4 border rounded-lg bg-gray-50">
        <h3 class="font-semibold flex items-center gap-2 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 12.414A8 8 0 1112.414 13.414l4.243 4.243z" />
          </svg>
          Alamat Pengiriman
        </h3>
        <div><?= e($order['address_detail']) ?></div>
        <div class="text-sm text-gray-600"><?= e($order['regency_name']) ?>, <?= e($order['province_name']) ?> — <?= e($order['postal_code']) ?></div>
      </div>
    </div>

    <!-- Bagian tabel produk -->
<div class="overflow-x-auto mb-6">
  <table class="min-w-full border text-sm rounded-lg overflow-hidden">
    <thead class="bg-indigo-50 text-gray-700">
      <tr>
        <th class="px-3 py-2 text-left">Produk</th>
        <th class="px-3 py-2 text-right">Harga</th>
        <th class="px-3 py-2 text-center">Qty</th>
        <th class="px-3 py-2 text-right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $total = 0;
      while ($it = $items->fetch_assoc()):
        $sub = $it['quantity'] * $it['price'];
        $total += $sub;

        // path gambar produk
        $productImage = !empty($it['image']) 
          ? "../assets/uploads/products/".e($it['image']) 
          : "../assets/no-image.png";
      ?>
      <tr class="border-b">
        <td class="px-3 py-2 flex items-center gap-2">
          <img src="<?= $productImage ?>" alt="<?= e($it['name']) ?>" class="w-12 h-12 object-cover rounded">
          <span><?= e($it['name']) ?></span>
        </td>
        <td class="px-3 py-2 text-right"><?= rupiah($it['price']) ?></td>
        <td class="px-3 py-2 text-center"><?= (int)$it['quantity'] ?></td>
        <td class="px-3 py-2 text-right"><?= rupiah($sub) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

    <!-- Ringkasan -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
      <?php
        $grand = (float)$order['total_amount'];
        $shippingOnly = $grand - $total;
      ?>
      <div class="flex justify-between mb-1">
        <span>Total Produk</span>
        <span><?= rupiah($total) ?></span>
      </div>
      <div class="flex justify-between mb-1">
        <span>Ongkir</span>
        <span><?= rupiah($shippingOnly) ?></span>
      </div>
      <div class="flex justify-between font-bold text-lg">
        <span>Grand Total</span>
        <span><?= rupiah($grand) ?></span>
      </div>
    </div>

    <!-- Payment -->
    <div class="p-4 border rounded-lg bg-white mb-6">
      <h3 class="font-semibold flex items-center gap-2 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v1H7v4h10v-4h-2v-1c0-1.657-1.343-3-3-3z" />
        </svg>
        Pembayaran
      </h3>
      <?php if ($payment): ?>
        <div>Metode: <span class="font-medium"><?= strtoupper($payment['method']) ?></span></div>
        <div>Status: 
          <span class="px-2 py-0.5 rounded text-sm 
            <?= $payment['status']==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
            <?= ucfirst($payment['status']) ?>
          </span>
        </div>
      <?php else: ?>
        <p class="text-gray-500">Belum ada data pembayaran.</p>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3">
  <a href="../index.php" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Kembali Belanja</a>
  <?php if ($payment && $payment['status'] === 'pending'): ?>
    <a href="payment_confirm.php?order_id=<?= $order['id'] ?>" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
      Konfirmasi Pembayaran
    </a>
  <?php endif; ?>
  <a href="invoice_pdf.php?order_id=<?= $order['id'] ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
    Download Invoice (PDF)
  </a>
</div>

<?php include "../includes/footer.php"; ?>
