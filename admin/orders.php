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

// Query
$sql = "SELECT o.*, u.name as customer_name, u.email, u.phone, a.detail as address 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN addresses a ON o.address_id = a.id";

if ($search) {
    $sql .= " WHERE o.order_number LIKE ? OR u.name LIKE ?";
}

$sql .= " ORDER BY o.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if ($search) {
    $like = "%$search%";
    $stmt->bind_param("ssii", $like, $like, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

include "../includes/header.php";
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">📦 Manajemen Pesanan</h1>
    <a href="index.php" 
       class="bg-gray-600 text-white px-4 py-2 rounded flex items-center gap-2 hover:bg-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
      </svg>
      Dashboard
    </a>
  </div>

  <!-- Form Pencarian -->
  <form method="GET" class="flex">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari pesanan..."
           class="border rounded-l px-3 py-2 focus:outline-none w-64">
    <button class="bg-blue-600 text-white px-4 rounded-r hover:bg-blue-700">Cari</button>
  </form>
</div>

<div class="overflow-x-auto">
  <table class="w-full border border-gray-200 text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">No Pesanan</th>
        <th class="p-3">Customer</th>
        <th class="p-3">Email</th>
        <th class="p-3">No HP</th>
        <th class="p-3">Alamat</th>
        <th class="p-3">Total</th>
        <th class="p-3">Status</th>
        <th class="p-3">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr class="border-b hover:bg-gray-50">
        <td class="p-3 font-mono"><?= e($row['order_number']) ?></td>
        <td class="p-3"><?= e($row['customer_name']) ?></td>
        <td class="p-3"><?= e($row['email']) ?></td>
        <td class="p-3"><?= e($row['phone']) ?></td>
        <td class="p-3"><?= e($row['address']) ?></td>
        <td class="p-3">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
        <td class="p-3">
          <form method="POST" action="update_order_status.php" class="inline">
            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
            <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
              <?php foreach(['pending','paid','shipped','completed','cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= $row['status']===$status?'selected':'' ?>>
                  <?= ucfirst($status) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="p-3 flex gap-2">
          <!-- Tombol Detail -->
          <a href="order_detail.php?id=<?= $row['id'] ?>" 
             class="bg-green-500 text-white px-3 py-1 rounded flex items-center gap-1 hover:bg-green-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Detail
          </a>
          <!-- Tombol Hapus -->
          <a href="delete_order.php?id=<?= $row['id'] ?>" 
             onclick="return confirm('Yakin hapus pesanan ini?')"
             class="bg-red-500 text-white px-3 py-1 rounded flex items-center gap-1 hover:bg-red-600">
            🗑 Hapus
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div class="flex justify-center mt-4">
  <?php for ($i=1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="px-3 py-1 border rounded mx-1 <?= $page==$i?'bg-blue-600 text-white':'bg-white hover:bg-gray-100' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
</div>

<?php include "../includes/footer.php"; ?>
