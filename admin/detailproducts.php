<?php
include "../config/database.php";
include "../includes/functions.php";
requireAdmin();

// ambil ID produk dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('products.php');
}
$id = (int)$_GET['id'];

// ambil data produk
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    redirect('products.php');
}
?>
<?php include "../includes/header.php"; ?>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold flex items-center gap-2">
      <!-- icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
      </svg>
      Detail Produk
    </h1>

    <a href="products.php" class="flex items-center gap-2 bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
      <!-- back icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Kembali
    </a>
  </div>

  <div class="bg-white p-6 rounded-lg shadow grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="flex justify-center">
      <?php if ($product['image']): ?>
        <img src="../assets/uploads/products/<?= e($product['image']) ?>" class="h-64 w-full object-cover rounded" alt="<?= e($product['name']) ?>">
      <?php else: ?>
        <div class="h-64 w-full bg-gray-200 rounded flex items-center justify-center text-gray-500">No Image</div>
      <?php endif; ?>
    </div>

    <div class="md:col-span-2 flex flex-col gap-2">
      <h2 class="text-xl font-bold"><?= e($product['name']) ?></h2>
      <p class="text-gray-600"><strong>Kategori:</strong> <?= e($product['category_name'] ?? '-') ?></p>
      <p class="text-gray-600"><strong>Harga:</strong> Rp <?= number_format($product['price'],0,',','.') ?></p>
      <p class="text-gray-600"><strong>Stok:</strong> <?= (int)$product['stock'] ?></p>
      <p class="text-gray-700 mt-3"><?= nl2br(e($product['description'])) ?></p>
    </div>
  </div>

  <!-- Tambahan: review/komentar bisa ditambahkan disini -->
</div>

<?php include "../includes/footer.php"; ?>