<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

if (!isset($_GET['id'])) {
    redirect("orders.php");
}

$orderId = (int) $_GET['id'];
$stmt = $conn->prepare("
    SELECT o.*, u.name AS customer_name, u.email, u.phone, a.detail AS address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Ambil item pesanan
$itemStmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name, p.price AS product_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result();

include "../includes/header.php";
?>

<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-md mt-6">
  <h2 class="text-xl font-bold mb-4">📋 Detail Pesanan</h2>
  <?php if ($order): ?>
    <div class="mb-4 space-y-1">
      <p><strong>No Pesanan:</strong> <?= e($order['order_number']) ?></p>
      <p><strong>Nama Customer:</strong> <?= e($order['customer_name']) ?></p>
      <p><strong>Email:</strong> <?= e($order['email']) ?></p>
      <p><strong>No HP:</strong> <?= e($order['phone']) ?></p>
      <p><strong>Alamat:</strong> <?= e($order['address']) ?></p>
      <p><strong>Total Belanja:</strong> Rp<?= number_format($order['total_amount'],0,',','.') ?></p>
      <p><strong>Status:</strong> 
        <span class="px-2 py-1 rounded text-white 
          <?php
            switch($order['status']){
              case 'pending': echo 'bg-yellow-500'; break;
              case 'paid': echo 'bg-blue-500'; break;
              case 'shipped': echo 'bg-purple-500'; break;
              case 'completed': echo 'bg-green-600'; break;
              case 'cancelled': echo 'bg-red-600'; break;
            }
          ?>">
          <?= ucfirst($order['status']) ?>
        </span>
      </p>
    </div>

    <!-- Tabel Item Pesanan -->
    <h3 class="text-lg font-semibold mb-2">🛒 Produk Dipesan</h3>
    <div class="overflow-x-auto">
      <table class="w-full border border-gray-200 text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-3 text-left">Produk</th>
            <th class="p-3">Harga Satuan</th>
            <th class="p-3">Jumlah</th>
            <th class="p-3">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($items->num_rows > 0): ?>
            <?php while ($item = $items->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="p-3"><?= e($item['product_name']) ?></td>
                <td class="p-3">Rp<?= number_format($item['price'],0,',','.') ?></td>
                <td class="p-3 text-center"><?= e($item['quantity']) ?></td>
                <td class="p-3 font-medium">Rp<?= number_format($item['price'] * $item['quantity'],0,',','.') ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="p-3 text-center text-gray-500">Tidak ada produk dalam pesanan ini.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-red-600">Pesanan tidak ditemukan.</p>
  <?php endif; ?>

  <div class="mt-4">
    <a href="orders.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">⬅ Kembali</a>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
