<?php
// admin/shipping.php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

// messages
$msg = "";
$msg_type = "";

/* ============================
   HANDLE FORM SUBMISSIONS
   ============================ */

// Tambah Provinsi
if (isset($_POST['add_province'])) {
    $name = trim($_POST['province_name']);
    if ($name === "") {
        $msg = "Nama provinsi tidak boleh kosong.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO provinces (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $msg = "Provinsi berhasil ditambahkan.";
            $msg_type = "success";
        } else {
            $msg = "Gagal menambah provinsi: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Tambah Regency (Kabupaten/Kota) dengan postal_code
if (isset($_POST['add_regency'])) {
    $province_id = (int)$_POST['province_id_for_regency'];
    $name = trim($_POST['regency_name']);
    $postal = trim($_POST['postal_code']);
    if ($province_id <= 0 || $name === "") {
        $msg = "Provinsi dan nama kabupaten/kota wajib diisi.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO regencies (province_id, name, postal_code) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $province_id, $name, $postal);
        if ($stmt->execute()) {
            $msg = "Kabupaten/Kota berhasil ditambahkan.";
            $msg_type = "success";
        } else {
            $msg = "Gagal menambah kabupaten/kota: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Tambah Ongkir (shipping_rate)
if (isset($_POST['add_shipping'])) {
    $regency_id = (int)$_POST['regency_id'];
    $courier = trim($_POST['courier']);
    $cost = (float)str_replace(',', '', $_POST['cost']);
    $est = trim($_POST['estimated_days']);
    if ($regency_id <= 0 || $courier === "" || $cost <= 0) {
        $msg = "Kabupaten, kurir, dan biaya ongkir wajib diisi dengan benar.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO shipping_rates (regency_id, courier, cost, estimated_days) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $regency_id, $courier, $cost, $est);
        if ($stmt->execute()) {
            $msg = "Ongkir berhasil ditambahkan.";
            $msg_type = "success";
        } else {
            $msg = "Gagal menambah ongkir: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Edit Ongkir
if (isset($_POST['edit_shipping'])) {
    $id = (int)$_POST['shipping_id'];
    $regency_id = (int)$_POST['edit_regency_id'];
    $courier = trim($_POST['edit_courier']);
    $cost = (float)str_replace(',', '', $_POST['edit_cost']);
    $est = trim($_POST['edit_estimated_days']);

    if ($id <= 0 || $regency_id <= 0 || $courier === "" || $cost <= 0) {
        $msg = "Data edit tidak lengkap atau tidak valid.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE shipping_rates SET regency_id=?, courier=?, cost=?, estimated_days=? WHERE id=?");
        $stmt->bind_param("isdsi", $regency_id, $courier, $cost, $est, $id);
        if ($stmt->execute()) {
            $msg = "Ongkir berhasil diperbarui.";
            $msg_type = "success";
        } else {
            $msg = "Gagal update ongkir: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// Hapus Ongkir
if (isset($_GET['delete_shipping'])) {
    $id = (int)$_GET['delete_shipping'];
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM shipping_rates WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Ongkir berhasil dihapus.";
            $msg_type = "success";
        } else {
            $msg = "Gagal menghapus ongkir: " . $conn->error;
            $msg_type = "error";
        }
    }
}

/* ============================
   FETCH DATA (for display)
   ============================ */

// provinces & regencies (for dropdowns)
$provinces = $conn->query("SELECT id, name FROM provinces ORDER BY name ASC");
$regencies_all = $conn->query("SELECT r.id, r.name, r.postal_code, p.name AS province_name FROM regencies r LEFT JOIN provinces p ON r.province_id = p.id ORDER BY p.name, r.name");

// search & pagination for shipping rates
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$where = "";
$params = [];
$types = "";

if ($search !== '') {
    $where = "WHERE r.name LIKE ? OR sr.courier LIKE ? OR p.name LIKE ?";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
    $types = "sss";
}

/* Hitung total data */
if ($where) {
    $countSql = "SELECT COUNT(*) AS total
                 FROM shipping_rates sr
                 JOIN regencies r ON sr.regency_id = r.id
                 JOIN provinces p ON r.province_id = p.id
                 $where";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query("SELECT COUNT(*) AS total FROM shipping_rates")->fetch_assoc()['total'];
}

$totalPages = max(1, ceil($total / $perPage));

/* Ambil data list ongkir */
if ($where) {
    $sql = "SELECT sr.*, r.name AS regency_name, r.postal_code, p.name AS province_name
            FROM shipping_rates sr
            JOIN regencies r ON sr.regency_id = r.id
            JOIN provinces p ON r.province_id = p.id
            $where
            ORDER BY p.name, r.name, sr.courier
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($types . "ii", ...$allParams);
} else {
    $sql = "SELECT sr.*, r.name AS regency_name, r.postal_code, p.name AS province_name
            FROM shipping_rates sr
            JOIN regencies r ON sr.regency_id = r.id
            JOIN provinces p ON r.province_id = p.id
            ORDER BY p.name, r.name, sr.courier
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$shippingList = $stmt->get_result();

include "../includes/header.php";
?>

<div class="p-6 max-w-7xl mx-auto">
  <div class="flex items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-bold flex items-center gap-3">
      <!-- Truck icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h6l3 4v4h-2a3 3 0 11-6 0H9a3 3 0 11-6 0H1v-6" />
      </svg>
      Manajemen Ongkir / Shipping
    </h1>

    <div class="flex items-center gap-3">
      <a href="index.php" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800 flex items-center gap-2">
        <!-- Dashboard icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3" />
        </svg>
        Dashboard
      </a>

      <button onclick="openAddShippingModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2">
        <!-- plus icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Ongkir
      </button>

      <button onclick="openAddRegencyModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-2">
        Tambah Kabupaten/Kota
      </button>

      <button onclick="openAddProvinceModal()" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 flex items-center gap-2">
        Tambah Provinsi
      </button>
    </div>
  </div>

  <!-- messages -->
  <?php if ($msg): ?>
    <div class="<?= $msg_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-3 rounded mb-4">
      <?= e($msg) ?>
    </div>
  <?php endif; ?>

  <!-- Search -->
  <div class="mb-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
    <form method="GET" class="flex w-full sm:w-auto gap-2">
      <input type="text" name="search" placeholder="Cari kabupaten / kurir / provinsi..." value="<?= e($search) ?>"
             class="px-3 py-2 border rounded-l w-full sm:w-80 focus:ring focus:ring-indigo-300">
      <button class="bg-indigo-600 text-white px-4 py-2 rounded-r hover:bg-indigo-700">Cari</button>
    </form>

    <div class="text-sm text-gray-600">
      Menampilkan <?= ($total == 0 ? 0 : ($offset + 1)) ?> - <?= min($offset + $perPage, $total) ?> dari <?= $total ?> ongkir
    </div>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left">No</th>
          <th class="px-4 py-3 text-left">Provinsi</th>
          <th class="px-4 py-3 text-left">Kabupaten / Kota (Kode Pos)</th>
          <th class="px-4 py-3 text-left">Kurir</th>
          <th class="px-4 py-3 text-right">Biaya</th>
          <th class="px-4 py-3 text-left">Estimasi</th>
          <th class="px-4 py-3 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = $offset + 1; ?>
        <?php while ($row = $shippingList->fetch_assoc()): ?>
          <tr class="border-t hover:bg-gray-50">
            <td class="px-4 py-3"><?= $no++ ?></td>
            <td class="px-4 py-3"><?= e($row['province_name']) ?></td>
            <td class="px-4 py-3">
              <?= e($row['regency_name']) ?>
              <div class="text-xs text-gray-500"><?= e($row['postal_code']) ?></div>
            </td>
            <td class="px-4 py-3"><?= e($row['courier']) ?></td>
            <td class="px-4 py-3 text-right"><?= rupiah($row['cost']) ?></td>
            <td class="px-4 py-3"><?= e($row['estimated_days']) ?></td>
            <td class="px-4 py-3 text-center flex items-center justify-center gap-2">
              <button onclick='openEditShipping(<?= json_encode($row) ?>)' class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 flex items-center gap-1 text-sm">
                <!-- edit -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6M9 7v10a2 2 0 002 2h6" />
                </svg>
                Edit
              </button>

              <a href="?delete_shipping=<?= $row['id'] ?>" onclick="return confirm('Hapus ongkir ini?')" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm flex items-center gap-1">
                <!-- trash -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 7m5 4v6m4-6v6M1 7h22" />
                </svg>
                Hapus
              </a>
            </td>
          </tr>
        <?php endwhile; ?>

        <?php if ($total == 0): ?>
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada data ongkir.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

 <!-- Pagination -->
<?php if ($totalPages > 1): ?>
  <div class="flex justify-center mt-6 space-x-1">
    <!-- Tombol Prev -->
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
         class="px-3 py-1 border rounded bg-white hover:bg-gray-100">« Prev</a>
    <?php endif; ?>

    <!-- Nomor Halaman -->
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
         class="px-3 py-1 border rounded <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <!-- Tombol Next -->
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
         class="px-3 py-1 border rounded bg-white hover:bg-gray-100">Next »</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- ===== Modals ===== -->

<!-- Add Province Modal -->
<div id="addProvinceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-full max-w-md">
    <h3 class="text-lg font-semibold mb-4">Tambah Provinsi</h3>
    <form method="POST" class="space-y-3">
      <div>
        <label class="block text-sm">Nama Provinsi</label>
        <input type="text" name="province_name" required class="w-full px-3 py-2 border rounded" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeAddProvinceModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="add_province" class="px-4 py-2 bg-indigo-600 text-white rounded">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Regency Modal -->
<div id="addRegencyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-full max-w-lg">
    <h3 class="text-lg font-semibold mb-4">Tambah Kabupaten / Kota</h3>
    <form method="POST" class="space-y-3">
      <div>
        <label class="block text-sm">Pilih Provinsi</label>
        <select name="province_id_for_regency" required class="w-full px-3 py-2 border rounded">
          <option value="">-- Pilih Provinsi --</option>
          <?php
            $provinces->data_seek(0);
            while ($p = $provinces->fetch_assoc()):
          ?>
            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm">Nama Kabupaten / Kota</label>
        <input type="text" name="regency_name" required class="w-full px-3 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm">Kode Pos (opsional)</label>
        <input type="text" name="postal_code" class="w-full px-3 py-2 border rounded" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeAddRegencyModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="add_regency" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Shipping Modal -->
<div id="addShippingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-full max-w-lg">
    <h3 class="text-lg font-semibold mb-4">Tambah Ongkir</h3>
    <form method="POST" class="space-y-3">
      <div>
        <label class="block text-sm">Pilih Kabupaten / Kota</label>
        <select name="regency_id" required class="w-full px-3 py-2 border rounded">
          <option value="">-- Pilih Kabupaten/Kota --</option>
          <?php
            $regencies_all->data_seek(0);
            while ($r = $regencies_all->fetch_assoc()):
          ?>
            <option value="<?= $r['id'] ?>"><?= e($r['province_name'] . " - " . $r['name'] . " (" . $r['postal_code'] . ")") ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm">Kurir</label>
        <select name="courier" required class="w-full px-3 py-2 border rounded">
          <option value="">-- Pilih Kurir --</option>
          <option>JNE</option>
          <option>POS Indonesia</option>
          <option>JNT</option>
          <option>SPX</option>
          <option>TIKI</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Biaya (angka saja)</label>
        <input type="number" name="cost" step="0.01" required class="w-full px-3 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm">Estimasi Hari (contoh: 2-3 hari)</label>
        <input type="text" name="estimated_days" class="w-full px-3 py-2 border rounded" />
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeAddShippingModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="add_shipping" class="px-4 py-2 bg-green-600 text-white rounded">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Shipping Modal -->
<div id="editShippingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg w-full max-w-lg">
    <h3 class="text-lg font-semibold mb-4">Edit Ongkir</h3>
    <form method="POST" class="space-y-3">
      <input type="hidden" name="shipping_id" id="edit_shipping_id">
      <div>
        <label class="block text-sm">Pilih Kabupaten / Kota</label>
        <select name="edit_regency_id" id="edit_regency_id" required class="w-full px-3 py-2 border rounded">
          <option value="">-- Pilih Kabupaten/Kota --</option>
          <?php
            // reload regencies list
            $regList = $conn->query("SELECT r.id, r.name, r.postal_code, p.name AS province_name FROM regencies r LEFT JOIN provinces p ON r.province_id = p.id ORDER BY p.name, r.name");
            while ($rr = $regList->fetch_assoc()):
          ?>
            <option value="<?= $rr['id'] ?>"><?= e($rr['province_name'] . " - " . $rr['name'] . " (" . $rr['postal_code'] . ")") ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm">Kurir</label>
        <select name="edit_courier" id="edit_courier" required class="w-full px-3 py-2 border rounded">
          <option>JNE</option>
          <option>POS Indonesia</option>
          <option>JNT</option>
          <option>SPX</option>
          <option>TIKI</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Biaya</label>
        <input type="number" step="0.01" name="edit_cost" id="edit_cost" required class="w-full px-3 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm">Estimasi Hari</label>
        <input type="text" name="edit_estimated_days" id="edit_estimated_days" class="w-full px-3 py-2 border rounded" />
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditShippingModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="edit_shipping" class="px-4 py-2 bg-yellow-500 text-white rounded">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal helpers
function openAddProvinceModal(){ document.getElementById('addProvinceModal').classList.remove('hidden'); document.getElementById('addProvinceModal').classList.add('flex'); }
function closeAddProvinceModal(){ document.getElementById('addProvinceModal').classList.add('hidden'); }

function openAddRegencyModal(){ document.getElementById('addRegencyModal').classList.remove('hidden'); document.getElementById('addRegencyModal').classList.add('flex'); }
function closeAddRegencyModal(){ document.getElementById('addRegencyModal').classList.add('hidden'); }

function openAddShippingModal(){ document.getElementById('addShippingModal').classList.remove('hidden'); document.getElementById('addShippingModal').classList.add('flex'); }
function closeAddShippingModal(){ document.getElementById('addShippingModal').classList.add('hidden'); }

function openEditShippingModal(){ document.getElementById('editShippingModal').classList.remove('hidden'); document.getElementById('editShippingModal').classList.add('flex'); }
function closeEditShippingModal(){ document.getElementById('editShippingModal').classList.add('hidden'); }

// populate edit modal
function openEditShipping(row) {
  // row is JSON object passed from onclick
  document.getElementById('edit_shipping_id').value = row.id;
  document.getElementById('edit_regency_id').value = row.regency_id;
  document.getElementById('edit_courier').value = row.courier;
  document.getElementById('edit_cost').value = row.cost;
  document.getElementById('edit_estimated_days').value = row.estimated_days || '';
  openEditShippingModal();
}
</script>

<?php include "../includes/footer.php"; ?>
