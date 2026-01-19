<?php
include "config/database.php";
include "includes/functions.php";

/**
 * Helper: bind params ke mysqli_stmt dengan call_user_func_array
 * Karena bind_param membutuhkan parameter sebagai reference, kita membuat array reference.
 * @param mysqli_stmt $stmt
 * @param string $types
 * @param array $params
 * @return bool
 */
function stmt_bind_params_ref($stmt, $types, $params) {
    if (empty($params)) return false;
    // buat array references
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

/* === Params Pagination & Filter === */
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

/* === Ambil daftar kategori (sidebar / dropdown) === */
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

/* === Build WHERE dan params dinamis === */
$whereParts = [];
$params = [];
$types = '';

if ($search !== '') {
    $whereParts[] = "p.name LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}
if ($category > 0) {
    $whereParts[] = "p.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

$whereSql = $whereParts ? "WHERE " . implode(" AND ", $whereParts) : "";

/* === Hitung total produk sesuai filter === */
$countSql = "SELECT COUNT(*) AS total FROM products p $whereSql";
$countStmt = $conn->prepare($countSql);
if ($whereSql) {
    stmt_bind_params_ref($countStmt, $types, $params);
}
$countStmt->execute();
$countRes = $countStmt->get_result()->fetch_assoc();
$total = (int)($countRes['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));

/* === Ambil daftar produk beserta kategori, dengan LIMIT/OFFSET === */
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $whereSql
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if ($whereSql) {
    $bindParams = $params;
    $bindParams[] = $perPage;
    $bindParams[] = $offset;
    $typesAll = $types . "ii";
    stmt_bind_params_ref($stmt, $typesAll, $bindParams);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$products = $stmt->get_result();

include "includes/header.php";
?>

<div class="container mx-auto p-4 max-w-7xl">
  <div class="flex flex-col md:flex-row gap-6">
    <!-- SIDEBAR KATEGORI -->
    <aside class="w-full md:w-1/4 bg-white p-4 rounded shadow">
      <h2 class="font-bold mb-3">Kategori</h2>
      <ul class="space-y-2">
        <li><a href="index.php" class="text-indigo-600 hover:underline <?= $category==0 ? 'font-semibold' : '' ?>">Semua Produk</a></li>
        <?php
        $categories->data_seek(0);
        while ($c = $categories->fetch_assoc()):
        ?>
        <li>
          <a href="index.php?category=<?= $c['id'] ?>" class="<?= $category == $c['id'] ? 'font-semibold text-indigo-700' : 'text-gray-700 hover:underline' ?>">
            <?= e($c['name']) ?>
          </a>
        </li>
        <?php endwhile; ?>
      </ul>

      <div class="mt-4 text-sm text-gray-600">
        Menampilkan <?= $total==0 ? 0 : ($offset+1) ?> - <?= min($offset+$perPage, $total) ?> dari <?= $total ?> produk
      </div>

      <!-- About & Contact -->
      <div class="mt-6 border-t pt-4 text-sm text-gray-600">
        <h4 class="font-semibold mb-2">Tentang Kami</h4>
        <p>Wawan Store - Store Fashion dan Casual dengan kualitas terbaik, harga merakyat dan memberikan pelayanan terbaik.</p>

        <h4 class="font-semibold mt-4 mb-2">Alamat Kami</h4>
        <p>Desa Bangun Jaya, RT. 08 RW. 02, Kec. Balai Riam Kab. Sukamara 74773</p>

        <h4 class="font-semibold mt-4 mb-2">Hubungi Kami</h4>
        <div class="flex flex-col gap-2 mt-2">
          <a href="https://instagram.com/maspos741?igsh=cXF0YWd5ZnBhNmk4" target="_blank" class="flex items-center gap-2 text-pink-500 hover:underline">
            <!-- Instagram Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9zm4.5 3a5.5 5.5 0 1 1 0 11a5.5 5.5 0 0 1 0-11zm0 2a3.5 3.5 0 1 0 0 7a3.5 3.5 0 0 0 0-7zm5.75-2.25a.75.75 0 1 1 0 1.5a.75.75 0 0 1 0-1.5z"/></svg>
            Instagram
          </a>
          <a href="https://facebook.com/share/1Ehmc3UtFU/" target="_blank" class="flex items-center gap-2 text-blue-600 hover:underline">
            <!-- Facebook Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2h3a1 1 0 0 1 1 1v3h-3.5A1.5 1.5 0 0 0 12 7.5V10h4l-1 4h-3v8h-4v-8H7v-4h3V7.5A4.5 4.5 0 0 1 14.5 3H17V2h-4z"/></svg>
            Facebook
          </a>
          <a href="https://wa.me/6282251373438" target="_blank" class="flex items-center gap-2 text-green-600 hover:underline">
            <!-- WhatsApp Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.51 0-9.96 4.45-9.96 9.96c0 1.76.47 3.48 1.36 4.99L2 22l5.25-1.38c1.45.79 3.08 1.2 4.79 1.2h.01c5.51 0 9.96-4.45 9.96-9.96S17.55 2 12.04 2zm0 18c-1.47 0-2.9-.39-4.14-1.13l-.3-.18l-3.12.82l.83-3.04l-.2-.31a7.96 7.96 0 0 1-1.25-4.31c0-4.41 3.59-8 8-8c2.14 0 4.15.83 5.66 2.34A7.96 7.96 0 0 1 20.04 12c0 4.41-3.59 8-8 8z"/></svg>
            WhatsApp: 082251373438
          </a>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1">
      <!-- Search -->
      <form method="get" class="mb-4 flex gap-2">
        <input type="text" name="search" placeholder="Cari produk..." value="<?= e($search) ?>" class="flex-1 px-3 py-2 border rounded-l focus:outline-none">
        <select name="category" class="px-3 border-t border-b">
          <option value="0">Semua Kategori</option>
          <?php
            $categories->data_seek(0);
            while ($cc = $categories->fetch_assoc()):
          ?>
            <option value="<?= $cc['id'] ?>" <?= $category == $cc['id'] ? 'selected' : '' ?>><?= e($cc['name']) ?></option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r">Cari</button>
      </form>

      <h1 class="text-2xl font-bold mb-4">Produk Terbaru</h1>

      <?php if ($products && $products->num_rows > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($p = $products->fetch_assoc()):
            $imgFile = !empty($p['image']) ? "assets/uploads/products/". $p['image'] : "assets/uploads/products/noimage.png";
            if (!file_exists($imgFile)) $imgFile = "assets/uploads/products/noimage.png";
          ?>
            <div class="bg-white rounded-lg shadow p-4 flex flex-col">
              <img src="<?= e($imgFile) ?>" alt="<?= e($p['name']) ?>" class="h-48 w-full object-cover rounded">
              <div class="mt-3">
                <h2 class="font-semibold"><?= e($p['name']) ?></h2>
                <div class="text-sm text-gray-500"><?= e($p['category_name'] ?? '-') ?></div>
                <div class="mt-2 font-semibold text-indigo-600"><?= rupiah($p['price']) ?></div>
              </div>
              <div class="mt-4 flex gap-2">
                <a href="product_detail.php?id=<?= $p['id'] ?>" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded text-center hover:bg-gray-300">Detail</a>
                <a href="customer/cart.php?add=<?= $p['id'] ?>" class="flex-1 bg-blue-600 text-white py-2 rounded text-center hover:bg-blue-700">Beli</a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
          <div class="text-sm text-gray-600">Halaman <?= $page ?> / <?= $totalPages ?></div>
          <div class="flex gap-2">
            <?php if ($page > 1): ?>
              <a href="?<?= http_build_query(['page' => $page-1, 'search' => $search, 'category' => $category]) ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a>
            <?php endif; ?>

            <?php for ($i=1; $i <= $totalPages; $i++): ?>
              <a href="?<?= http_build_query(['page' => $i, 'search' => $search, 'category' => $category]) ?>" class="px-3 py-1 border rounded <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-100' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <a href="?<?= http_build_query(['page' => $page+1, 'search' => $search, 'category' => $category]) ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>
            <?php endif; ?>
          </div>
        </div>

      <?php else: ?>
        <p class="text-gray-600">Produk tidak ditemukan.</p>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include "includes/footer.php"; ?>
