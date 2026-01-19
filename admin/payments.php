<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$where = "";
$searchLike = "%$search%";

if ($search !== "") {
    $where = "WHERE p.id LIKE ? OR o.order_number LIKE ? OR u.name LIKE ?";
}

// Count total
if ($where) {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        $where
    ");
    $countStmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
} else {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
    ");
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Query data
if ($where) {
    $stmt = $conn->prepare("
        SELECT p.*, o.order_number, u.name as customer_name, u.email 
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        $where
        ORDER BY p.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sssii", $searchLike, $searchLike, $searchLike, $limit, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, o.order_number, u.name as customer_name, u.email 
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        ORDER BY p.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

include "../includes/header.php";
?>

<div class="max-w-7xl mx-auto p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold flex items-center gap-2 text-blue-600">💳 Kelola Pembayaran</h1>
    <div>
      <a href="index.php" class="flex items-center gap-2 bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
        </svg>
        Dashboard
      </a>
    </div>
  </div>

  <!-- Search -->
  <form method="GET" class="mb-4 flex gap-2">
    <input type="text" name="search" value="<?= e($search) ?>"
      class="w-full p-2 border rounded-lg focus:ring focus:ring-indigo-300"
      placeholder="Cari pembayaran (ID, No Pesanan, Nama)">
    <button type="submit" 
      class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">🔍 Cari</button>
  </form>

  <!-- Tabel Pembayaran -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="w-full text-left border-collapse">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-3 border">No Pesanan</th>
          <th class="p-3 border">Customer</th>
          <th class="p-3 border">Metode</th>
          <th class="p-3 border">Jumlah</th>
          <th class="p-3 border">Bukti</th>
          <th class="p-3 border">Status</th>
          <th class="p-3 border">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50">
              <td class="p-3 border"><?= e($row['order_number']) ?></td>
              <td class="p-3 border">
                <p class="font-semibold"><?= e($row['customer_name']) ?></p>
                <p class="text-sm text-gray-500"><?= e($row['email']) ?></p>
              </td>
              <td class="p-3 border"><?= ucfirst(e($row['method'])) ?></td>
              <td class="p-3 border">Rp<?= number_format($row['amount'],0,',','.') ?></td>
              <td class="p-3 border">
                <?php if (!empty($row['proof'])): ?>
                  <a href="../assets/uploads/payments/<?= e($row['proof']) ?>" target="_blank" 
                     class="text-blue-600 hover:underline flex items-center gap-1">
                     📎 Lihat
                  </a>
                <?php else: ?>
                  <span class="text-gray-400">-</span>
                <?php endif; ?>
              </td>
              <td class="p-3 border">
                <form method="POST" action="update_payment_status.php" class="flex items-center gap-2">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <select name="status" class="border rounded p-1 text-sm">
                    <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Belum Bayar</option>
                    <option value="confirmed" <?= $row['status'] === 'confirmed' ? 'selected' : '' ?>>Sudah Bayar</option>
                    <option value="failed" <?= $row['status'] === 'failed' ? 'selected' : '' ?>>Tidak Valid</option>
                  </select>
                  <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded text-sm hover:bg-green-700">✔</button>
                </form>
              </td>
              <td class="p-3 border">
                <a href="order_detail.php?id=<?= $row['order_id'] ?>" 
                   class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 flex items-center gap-1 text-sm">
                  👁 Detail Order
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="p-3 text-center text-gray-500">Tidak ada pembayaran ditemukan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="flex justify-center mt-6 space-x-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
         class="px-3 py-1 rounded-lg border <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
