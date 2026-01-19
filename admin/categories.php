<?php
include "../config/database.php";
include "../includes/functions.php";
requireAdmin();

// Handle Tambah
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?,?)");
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();
    header("Location: categories.php");
    exit;
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $desc, $id);
    $stmt->execute();
    header("Location: categories.php");
    exit;
}

// Handle Hapus
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: categories.php");
    exit;
}

// Urut ID kategori naik (1,2,3...)
$result = $conn->query("SELECT * FROM categories ORDER BY id ASC");
?>
<?php include "../includes/header.php"; ?>


<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
      </svg>
      Manajemen Kategori
    </h1>
    <a href="index.php" 
       class="flex items-center gap-1 bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
      </svg>
      Dashboard
    </a>
  </div>

  <!-- Form Tambah -->
  <div class="bg-white p-4 rounded-lg shadow mb-6">
    <form method="POST" class="grid md:grid-cols-3 gap-4">
      <input type="text" name="name" placeholder="Nama Kategori" required 
             class="px-3 py-2 border rounded focus:ring w-full">
      <input type="text" name="description" placeholder="Deskripsi" required 
             class="px-3 py-2 border rounded focus:ring w-full">
      <button type="submit" name="add" 
              class="flex items-center justify-center bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah
      </button>
    </form>
  </div>

  <!-- Tabel -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full text-sm">
      <thead class="bg-blue-600 text-white">
        <tr>
          <th class="px-4 py-2 text-left">Nama</th>
          <th class="px-4 py-2 text-left">Deskripsi</th>
          <th class="px-4 py-2 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="px-4 py-2"><?= e($row['name']) ?></td>
          <td class="px-4 py-2"><?= e($row['description']) ?></td>
          <td class="px-4 py-2 text-center flex gap-2 justify-center">
            <!-- Tombol Edit -->
            <button onclick="openEditModal(<?= $row['id'] ?>,'<?= e($row['name']) ?>','<?= e($row['description']) ?>')" 
              class="bg-yellow-500 text-white px-3 py-1 rounded flex items-center hover:bg-yellow-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17h2m-1-1V7m0 10a5 5 0 100-10 5 5 0 000 10z" />
              </svg>Edit
            </button>
            <!-- Tombol Hapus -->
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus kategori ini?')" 
               class="bg-red-600 text-white px-3 py-1 rounded flex items-center hover:bg-red-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>Hapus
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
    <h2 class="text-xl font-bold mb-4">Edit Kategori</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="id" id="editId">
      <div>
        <label class="block">Nama Kategori</label>
        <input type="text" name="name" id="editName" required class="w-full px-3 py-2 border rounded focus:ring">
      </div>
      <div>
        <label class="block">Deskripsi</label>
        <input type="text" name="description" id="editDesc" required class="w-full px-3 py-2 border rounded focus:ring">
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border">Batal</button>
        <button type="submit" name="edit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(id, name, desc) {
  document.getElementById('editId').value = id;
  document.getElementById('editName').value = name;
  document.getElementById('editDesc').value = desc;
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editModal').classList.add('flex');
}
function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include "../includes/footer.php"; ?>
