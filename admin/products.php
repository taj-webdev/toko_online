<?php
// admin/products.php
include "../config/database.php";
include "../includes/functions.php";
requireAdmin();

// helper upload (internal)
function uploadProductImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return false;
    // validate
    $allowed = ['jpg','jpeg','png','gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > $maxSize) return false;

    // ensure dir exists
    $uploadDir = __DIR__ . '/../assets/uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // unique filename
    try {
        $rand = bin2hex(random_bytes(4));
    } catch (Exception $e) {
        $rand = uniqid();
    }
    $filename = time() . '_' . $rand . '.' . $ext;
    $target = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }
    return false;
}

// messages
$msg = '';
$msg_type = '';

// Handle Tambah Produk
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float)str_replace(',', '', $_POST['price']);
    $stock = (int)$_POST['stock'];
    $imageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = uploadProductImage($_FILES['image']);
        if ($uploaded === false) {
            $msg = "Gagal upload gambar (ekstensi/size tidak sesuai). Maks 2MB, jpg/png/gif.";
            $msg_type = 'error';
        } else {
            $imageName = $uploaded;
        }
    }

    if ($msg === '') {
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issdis", $category_id, $name, $description, $price, $stock, $imageName);
        if ($stmt->execute()) {
            $msg = "Produk berhasil ditambahkan.";
            $msg_type = 'success';
            // redirect supaya form tidak resubmit
            header("Location: products.php");
            exit;
        } else {
            $msg = "Error menyimpan produk: " . $conn->error;
            $msg_type = 'error';
        }
    }
}

// Handle Edit Produk
if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price = (float)str_replace(',', '', $_POST['price']);
    $stock = (int)$_POST['stock'];

    // ambil nama gambar lama
    $oldImg = null;
    $q = $conn->prepare("SELECT image FROM products WHERE id=? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $res = $q->get_result();
    if ($r = $res->fetch_assoc()) {
        $oldImg = $r['image'];
    }

    // handle upload baru
    $newImage = $oldImg;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = uploadProductImage($_FILES['image']);
        if ($uploaded === false) {
            $msg = "Gagal upload gambar (ekstensi/size tidak sesuai). Maks 2MB, jpg/png/gif.";
            $msg_type = 'error';
        } else {
            $newImage = $uploaded;
            // hapus file lama jika ada
            if ($oldImg) {
                $oldPath = __DIR__ . '/../assets/uploads/products/' . $oldImg;
                if (file_exists($oldPath)) @unlink($oldPath);
            }
        }
    }

    if ($msg === '') {
        $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?");
        $stmt->bind_param("issdisi", $category_id, $name, $description, $price, $stock, $newImage, $id);
        if ($stmt->execute()) {
            $msg = "Produk berhasil diupdate.";
            $msg_type = 'success';
            header("Location: products.php");
            exit;
        } else {
            $msg = "Error update produk: " . $conn->error;
            $msg_type = 'error';
        }
    }
}

// Handle Hapus Produk
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // ambil nama gambar
    $q = $conn->prepare("SELECT image FROM products WHERE id=? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $res = $q->get_result();
    if ($row = $res->fetch_assoc()) {
        $img = $row['image'];
        if ($img) {
            $path = __DIR__ . '/../assets/uploads/products/' . $img;
            if (file_exists($path)) @unlink($path);
        }
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: products.php");
        exit;
    } else {
        $msg = "Gagal menghapus produk.";
        $msg_type = 'error';
    }
}

/* ========== Pagination & Search ========== */
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// total count
if ($search !== '') {
    $like = "%{$search}%";
    $cntStmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE name LIKE ? OR description LIKE ?");
    $cntStmt->bind_param("ss", $like, $like);
    $cntStmt->execute();
    $cntRes = $cntStmt->get_result()->fetch_assoc();
    $total = (int)$cntRes['total'];

    $listStmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ? OR p.description LIKE ? ORDER BY p.id ASC LIMIT ?, ?");
    $listStmt->bind_param("ssii", $like, $like, $offset, $perPage);
    $listStmt->execute();
    $result = $listStmt->get_result();
} else {
    $cntRes = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc();
    $total = (int)$cntRes['total'];

    $listStmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC LIMIT ?, ?");
    $listStmt->bind_param("ii", $offset, $perPage);
    $listStmt->execute();
    $result = $listStmt->get_result();
}

$totalPages = max(1, ceil($total / $perPage));

/* Ambil semua kategori untuk dropdown */
$catsRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>
<?php include "../includes/header.php"; ?>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold flex items-center gap-2">
      <!-- Icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
      </svg>
      Kelola Produk
    </h1>

    <div class="flex items-center gap-3">
      <a href="index.php" class="flex items-center gap-2 bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
        <!-- Home icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
        </svg>
        Dashboard
      </a>
    </div>
  </div>

  <!-- message -->
  <?php if ($msg): ?>
    <div class="<?= $msg_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-3 rounded mb-4">
      <?= e($msg) ?>
    </div>
  <?php endif; ?>

  <!-- Add Product Form -->
  <div class="bg-white p-4 rounded-lg shadow mb-6">
    <h2 class="text-lg font-semibold mb-3 flex items-center gap-2">
      <!-- plus icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Tambah Produk
    </h2>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <input type="text" name="name" placeholder="Nama Produk" required class="px-3 py-2 border rounded w-full" />
      <select name="category_id" required class="px-3 py-2 border rounded w-full">
        <option value="">-- Pilih Kategori --</option>
        <?php while($c = $catsRes->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endwhile; ?>
      </select>
      <input type="number" step="0.01" name="price" placeholder="Harga (contoh 15000.00)" required class="px-3 py-2 border rounded w-full" />
      <input type="number" name="stock" placeholder="Stock" required class="px-3 py-2 border rounded w-full" />
      <input type="file" name="image" accept="image/*" class="px-3 py-2 border rounded w-full" />
      <textarea name="description" placeholder="Deskripsi produk" class="px-3 py-2 border rounded w-full md:col-span-3"></textarea>

      <div class="md:col-span-3 flex justify-end">
        <button type="submit" name="add_product" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2">
          <!-- save icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Simpan Produk
        </button>
      </div>
    </form>
  </div>

  <!-- Search -->
  <div class="flex flex-col sm:flex-row gap-3 items-center justify-between mb-4">
    <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama / deskripsi..." class="px-3 py-2 border rounded w-full sm:w-64" />
      <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded hover:bg-indigo-700">Cari</button>
      <?php if ($search !== ''): ?>
        <a href="products.php" class="ml-2 text-sm text-gray-600 hover:underline">Reset</a>
      <?php endif; ?>
    </form>

    <div class="text-sm text-gray-600">
      Menampilkan <?= ($total === 0 ? 0 : ($offset+1)) ?> sampai <?= min($offset + $perPage, $total) ?> dari <?= $total ?> produk
    </div>
  </div>

  <!-- Table / Grid -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left">#</th>
          <th class="px-4 py-2 text-left">Gambar</th>
          <th class="px-4 py-2 text-left">Nama</th>
          <th class="px-4 py-2 text-left">Kategori</th>
          <th class="px-4 py-2 text-left">Harga</th>
          <th class="px-4 py-2 text-left">Stock</th>
          <th class="px-4 py-2 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = $offset + 1; while($row = $result->fetch_assoc()): ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="px-4 py-2"><?= $i++ ?></td>
          <td class="px-4 py-2">
            <?php if ($row['image']): ?>
              <img src="../assets/uploads/products/<?= e($row['image']) ?>" class="h-16 w-16 object-cover rounded" alt="<?= e($row['name']) ?>">
            <?php else: ?>
              <div class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-500">No</div>
            <?php endif; ?>
          </td>
          <td class="px-4 py-2"><?= e($row['name']) ?></td>
          <td class="px-4 py-2"><?= e($row['category_name'] ?? '-') ?></td>
          <td class="px-4 py-2">Rp <?= number_format($row['price'],0,',','.') ?></td>
          <td class="px-4 py-2"><?= (int)$row['stock'] ?></td>
          <td class="px-4 py-2 text-center flex gap-2 justify-center">
            <a href="detailproducts.php?id=<?= $row['id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 flex items-center gap-1">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12A3 3 0 119 12a3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Detail
            </a>
            </button>
            <button onclick='openEditModal(<?= $row['id'] ?>, <?= json_encode($row['category_id']) ?>, <?= json_encode($row['name']) ?>, <?= json_encode($row['description']) ?>, <?= json_encode($row['price']) ?>, <?= json_encode($row['stock']) ?>, <?= json_encode($row['image']) ?>)' class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 flex items-center gap-1">
              <!-- edit -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6M9 7v10a2 2 0 002 2h6" />
              </svg>
              Edit
            </button>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus produk ini?')" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 flex items-center gap-1">
              <!-- trash -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 7m5 4v6m4-6v6M1 7h22" />
              </svg>
              Hapus
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total === 0): ?>
          <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada produk.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="flex items-center justify-between mt-4">
    <div>
      <!-- nothing -->
    </div>
    <div class="flex gap-2 items-center">
      <?php if ($page > 1): ?>
        <a href="?<?= ($search !== '' ? 'q=' . urlencode($search) . '&' : '') ?>page=<?= $page-1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a>
      <?php endif; ?>

      <span class="text-sm text-gray-600">Halaman <?= $page ?> / <?= $totalPages ?></span>

      <?php if ($page < $totalPages): ?>
        <a href="?<?= ($search !== '' ? 'q=' . urlencode($search) . '&' : '') ?>page=<?= $page+1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Detail Produk</h3>
      <button onclick="closeDetailModal()" class="text-gray-600">✖</button>
    </div>
    <div id="detailContent" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- isi via JS -->
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-xl">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Edit Produk</h3>
      <button onclick="closeEditModal()" class="text-gray-600">✖</button>
    </div>

    <form method="POST" enctype="multipart/form-data" id="editForm" class="space-y-4">
      <input type="hidden" name="id" id="edit_id">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm">Nama Produk</label>
          <input type="text" name="name" id="edit_name" required class="px-3 py-2 border rounded w-full">
        </div>
        <div>
          <label class="block text-sm">Kategori</label>
          <select name="category_id" id="edit_category" required class="px-3 py-2 border rounded w-full">
            <option value="">-- Pilih Kategori --</option>
            <?php
            // reload categories
            $cats = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
            while($c = $cats->fetch_assoc()):
            ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Harga</label>
          <input type="number" step="0.01" name="price" id="edit_price" required class="px-3 py-2 border rounded w-full">
        </div>
        <div>
          <label class="block text-sm">Stock</label>
          <input type="number" name="stock" id="edit_stock" required class="px-3 py-2 border rounded w-full">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm">Deskripsi</label>
          <textarea name="description" id="edit_description" class="px-3 py-2 border rounded w-full"></textarea>
        </div>
        <div>
          <label class="block text-sm">Gambar (opsional, ganti jika perlu)</label>
          <input type="file" name="image" accept="image/*" class="px-3 py-2 border rounded w-full" />
        </div>
        <div id="currentImage" class="flex items-center gap-3">
          <!-- preview -->
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="edit_product" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
// edit modal open (populate)
function openEditModal(id, categoryId, name, desc, price, stock, image) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_category').value = categoryId;
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_stock').value = stock;
  document.getElementById('edit_description').value = desc;

  // show current image
  const cur = document.getElementById('currentImage');
  cur.innerHTML = '';
  if (image) {
    const img = document.createElement('img');
    img.src = '../assets/uploads/products/' + image;
    img.className = 'h-24 w-24 object-cover rounded';
    cur.appendChild(img);
  } else {
    cur.innerHTML = '<div class="h-24 w-24 bg-gray-200 rounded flex items-center justify-center text-gray-500">No Image</div>';
  }

  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editModal').classList.add('flex');
}
function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
  // clear file input (optional)
  const fi = document.querySelector('#editForm input[type="file"]');
  if (fi) fi.value = '';
}

// detail modal open
function openDetailModal(obj) {
  // obj is the whole product object (from json_encode)
  const modal = document.getElementById('detailModal');
  const content = document.getElementById('detailContent');
  content.innerHTML = '';

  // image column
  const imgCol = document.createElement('div');
  imgCol.className = 'flex items-center justify-center';
  if (obj.image) {
    const img = document.createElement('img');
    img.src = '../assets/uploads/products/' + obj.image;
    img.className = 'w-full max-w-sm object-cover rounded';
    imgCol.appendChild(img);
  } else {
    imgCol.innerHTML = '<div class="h-48 w-48 bg-gray-200 rounded flex items-center justify-center text-gray-500">No Image</div>';
  }

  // details column
  const detCol = document.createElement('div');
  detCol.className = 'md:col-span-2';
  detCol.innerHTML = `
    <h3 class="text-xl font-semibold mb-2">${escapeHtml(obj.name)}</h3>
    <p class="text-sm text-gray-600 mb-2"><strong>Kategori:</strong> ${escapeHtml(obj.category_name || '-')}</p>
    <p class="text-sm text-gray-600 mb-2"><strong>Harga:</strong> Rp ${Number(obj.price).toLocaleString('id-ID')}</p>
    <p class="text-sm text-gray-600 mb-2"><strong>Stok:</strong> ${escapeHtml(String(obj.stock))}</p>
    <p class="text-sm text-gray-700 mt-3">${escapeHtml(obj.description || '')}</p>
  `;

  content.appendChild(imgCol);
  content.appendChild(detCol);

  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
function closeDetailModal() {
  document.getElementById('detailModal').classList.add('hidden');
}
function escapeHtml(unsafe) {
  if (!unsafe) return '';
  return String(unsafe)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
</script>

<?php include "../includes/footer.php"; ?>
