<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Query total
if ($search) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE name LIKE ? OR email LIKE ?");
    $like = "%$search%";
    $stmtCount->bind_param("ss", $like, $like);
    $stmtCount->execute();
    $total = $stmtCount->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
}
$totalPages = max(1, ceil($total / $limit));

// Query data
if ($search) {
    $sql = "SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $like, $like, $limit, $offset);
} else {
    $sql = "SELECT * FROM users ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$users = $stmt->get_result();

include "../includes/header.php";
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
  <div class="flex items-center gap-3">
    <h1 class="text-2xl font-bold">👥 Manajemen Pengguna</h1>
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
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau email..."
           class="border rounded-l px-3 py-2 focus:outline-none w-64">
    <button class="bg-blue-600 text-white px-4 rounded-r hover:bg-blue-700">Cari</button>
  </form>
</div>

<div class="mb-4">
  <a href="add_user.php" 
     class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 hover:bg-green-700 w-fit">
    ➕ Tambah Pengguna
  </a>
</div>

<div class="overflow-x-auto">
  <table class="w-full border border-gray-200 text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="p-3 text-left">ID</th>
        <th class="p-3">Nama</th>
        <th class="p-3">Email</th>
        <th class="p-3">No HP</th>
        <th class="p-3">Role</th>
        <th class="p-3">Tanggal Daftar</th>
        <th class="p-3">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $users->fetch_assoc()): ?>
      <tr class="border-b hover:bg-gray-50">
        <td class="p-3"><?= e($row['id']) ?></td>
        <td class="p-3"><?= e($row['name']) ?></td>
        <td class="p-3"><?= e($row['email']) ?></td>
        <td class="p-3"><?= e($row['phone'] ?? '-') ?></td>
        <td class="p-3 capitalize">
          <?php if ($row['role'] === 'admin'): ?>
            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs">Admin</span>
          <?php else: ?>
            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">Customer</span>
          <?php endif; ?>
        </td>
        <td class="p-3"><?= e($row['created_at'] ?? '-') ?></td>
        <td class="p-3 flex gap-2">
          <a href="edit_user.php?id=<?= $row['id'] ?>" 
             class="bg-yellow-500 text-white px-3 py-1 rounded flex items-center gap-1 hover:bg-yellow-600">
            ✏️ Edit
          </a>
          <a href="delete_user.php?id=<?= $row['id'] ?>" 
             onclick="return confirm('Yakin hapus pengguna ini?')"
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
<div class="flex justify-center mt-4 flex-wrap gap-2">
  <?php for ($i=1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="px-3 py-1 border rounded <?= $page==$i?'bg-blue-600 text-white':'bg-white hover:bg-gray-100' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
</div>

<?php include "../includes/footer.php"; ?>
