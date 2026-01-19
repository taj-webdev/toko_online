<?php
include "config/database.php";
include "includes/functions.php";
include "includes/header.php";

// Ambil ID dari query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query ambil produk + kategori
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

?>

<div class="container mx-auto p-4 max-w-5xl">
  <?php if ($product): ?>
    <div class="bg-white shadow rounded-lg p-6 flex flex-col md:flex-row gap-6">
      
      <!-- Gambar Produk -->
      <div class="md:w-1/2">
        <?php
          $imgFile = !empty($product['image']) ? "assets/uploads/products/".$product['image'] : "assets/uploads/products/noimage.png";
          if (!file_exists($imgFile)) $imgFile = "assets/uploads/products/noimage.png";
        ?>
        <img src="<?= e($imgFile) ?>" 
             alt="<?= e($product['name']) ?>" 
             class="w-full h-80 object-cover rounded">
      </div>

      <!-- Detail Produk -->
      <div class="md:w-1/2 flex flex-col">
        <h1 class="text-2xl font-bold mb-2"><?= e($product['name']) ?></h1>
        <div class="text-gray-500 mb-2">Kategori: <?= e($product['category_name'] ?? '-') ?></div>
        <div class="text-xl font-semibold text-indigo-600 mb-4"><?= rupiah($product['price']) ?></div>

        <div class="mb-2"><span class="font-semibold">Stok:</span> <?= e($product['stock'] ?? 0) ?></div>

        <div class="mb-4">
          <span class="font-semibold">Deskripsi:</span>
          <p class="text-gray-700 mt-1 whitespace-pre-line"><?= nl2br(e($product['description'] ?? 'Tidak ada deskripsi.')) ?></p>
        </div>

        <div class="mt-auto flex gap-3">
          <a href="customer/cart.php?add=<?= $product['id'] ?>" 
             class="flex-1 bg-blue-600 text-white py-3 rounded text-center hover:bg-blue-700">
             + Keranjang
          </a>
          <a href="index.php" 
             class="flex-1 bg-gray-200 text-gray-800 py-3 rounded text-center hover:bg-gray-300">
             Kembali
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <p class="text-center text-red-500">Produk tidak ditemukan.</p>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
