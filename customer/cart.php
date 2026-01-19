<?php
session_start();
include "../config/database.php";
include "../includes/functions.php";
include "../includes/header.php";

// Pastikan cart ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambah produk ke cart (via ?add=ID)
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];

    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = 1;
    } else {
        $_SESSION['cart'][$id]++;
    }
    header("Location: cart.php");
    exit;
}

// Hapus produk dari cart (via ?remove=ID)
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit;
}

// Update jumlah produk di cart (form POST qty[])
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $qty) {
            $id = (int)$id;
            $qty = max(1, (int)$qty); // minimal 1
            if ($id > 0) $_SESSION['cart'][$id] = $qty;
        }
    }
    header("Location: cart.php");
    exit;
}

// Ambil detail produk di cart
$cartItems = [];
$totalBelanja = 0;

if (!empty($_SESSION['cart'])) {
    // safety: build list of ints
    $idsArr = array_map('intval', array_keys($_SESSION['cart']));
    $ids = implode(',', $idsArr);
    $sql = "SELECT * FROM products WHERE id IN ($ids)";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $row['qty'] = $_SESSION['cart'][$row['id']] ?? 0;
        $row['subtotal'] = $row['qty'] * $row['price'];
        $cartItems[] = $row;
        $totalBelanja += $row['subtotal'];
    }
}
?>

<div class="container mx-auto p-4 max-w-5xl">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
    <!-- Ikon keranjang -->
    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-6-9v9" />
    </svg>
    Keranjang Belanja
  </h1>

  <?php if (empty($cartItems)): ?>
    <div class="bg-white p-6 rounded shadow text-center">
      <p class="text-gray-600">Keranjang belanja kosong.</p>
      <a href="../index.php" class="inline-flex items-center gap-2 mt-4 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        <!-- Ikon belanja -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 10-8 0v4M5 11h14l-1 10H6L5 11z" />
        </svg>
        Belanja Sekarang
      </a>
    </div>
  <?php else: ?>
    <form method="post" action="cart.php" class="space-y-4">
      <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-700 align-middle">
            <tr>
              <th class="px-4 py-3 align-middle">Produk</th>
              <th class="px-4 py-3 text-left align-middle">Harga</th>
              <th class="px-4 py-3 text-left align-middle">Jumlah</th>
              <th class="px-4 py-3 text-left align-middle">Subtotal</th>
              <th class="px-4 py-3 text-left align-middle">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cartItems as $item):
              $imgFile = !empty($item['image']) ? "../assets/uploads/products/".$item['image'] : "../assets/uploads/products/noimage.png";
              if (!file_exists($imgFile)) $imgFile = "../assets/uploads/products/noimage.png";
            ?>
            <tr class="border-t hover:bg-gray-50">
              <!-- Produk: gambar + nama -->
              <td class="px-4 py-3 align-middle">
                <div class="flex items-center gap-3">
                  <img src="<?= e($imgFile) ?>" alt="<?= e($item['name']) ?>" class="w-14 h-14 object-cover rounded">
                  <div class="truncate">
                    <div class="font-medium text-gray-800"><?= e($item['name']) ?></div>
                    <div class="text-xs text-gray-500"><?= e($item['description'] ? (strlen($item['description'])>60?substr($item['description'],0,60)."...":$item['description']) : '') ?></div>
                  </div>
                </div>
              </td>

              <!-- Harga -->
              <td class="px-4 py-3 align-middle font-medium text-gray-700"><?= rupiah($item['price']) ?></td>

              <!-- Jumlah (input) -->
              <td class="px-4 py-3 align-middle">
                <div class="flex items-center gap-2">
                  <input type="number" name="qty[<?= (int)$item['id'] ?>]" value="<?= (int)$item['qty'] ?>" min="1"
                         class="w-20 border rounded px-2 py-1 text-center" />
                </div>
              </td>

              <!-- Subtotal -->
              <td class="px-4 py-3 align-middle font-semibold text-indigo-600"><?= rupiah($item['subtotal']) ?></td>

              <!-- Aksi -->
              <td class="px-4 py-3 align-middle">
                <a href="cart.php?remove=<?= (int)$item['id'] ?>" class="inline-flex items-center gap-2 text-red-600 hover:underline">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M10 3h4a1 1 0 011 1v2H9V4a1 1 0 011-1z" />
                  </svg>
                  Hapus
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Tombol update jumlah -->
      <div class="flex justify-end">
        <button type="submit" name="update_qty" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582M20 20v-5h-.581M5 9a7.003 7.003 0 0013.416 2H20" />
          </svg>
          Update Jumlah
        </button>
      </div>
    </form>

    <!-- Total & Buttons -->
    <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4">
      <div class="text-xl font-semibold flex items-center gap-2">
        <span class="inline-block">🛒 Total Belanja:</span>
        <span class="text-indigo-600"><?= rupiah($totalBelanja) ?></span>
      </div>

      <div class="flex gap-3">
        <a href="../index.php" class="inline-flex items-center gap-2 bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 10-8 0v4M5 11h14l-1 10H6L5 11z" />
          </svg>
          Lanjut Belanja
        </a>

        <a href="checkout.php" class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3-.672 3-1.5S13.657 8 12 8zM12 11c-1.657 0-3 .672-3 1.5S10.343 14 12 14s3-.672 3-1.5S13.657 11 12 11z" />
          </svg>
          Checkout
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
