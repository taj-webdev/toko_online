<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role  = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!$name || !$email || !$password) {
        $errors[] = "Nama, email, dan password wajib diisi.";
    } else {
        // cek email unik
        $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "Email sudah terdaftar, gunakan email lain.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name,email,phone,role,password,created_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->bind_param("sssss", $name, $email, $phone, $role, $password);
            if ($stmt->execute()) {
                redirect("users.php");
            } else {
                $errors[] = "Gagal menambah pengguna: " . $conn->error;
            }
        }
    }
}

include "../includes/header.php";
?>

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md mt-6">
  <h2 class="text-xl font-bold mb-4">➕ Tambah Pengguna</h2>

  <?php if ($errors): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
      <?= implode("<br>", $errors) ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block mb-1 font-medium">Nama</label>
      <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
    </div>
    <div>
      <label class="block mb-1 font-medium">Email</label>
      <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
    </div>
    <div>
      <label class="block mb-1 font-medium">No HP</label>
      <input type="text" name="phone" class="w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="block mb-1 font-medium">Password</label>
      <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
    </div>
    <div>
      <label class="block mb-1 font-medium">Role</label>
      <select name="role" class="w-full border rounded px-3 py-2">
        <option value="customer">Customer</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="flex justify-between">
      <a href="users.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">⬅ Kembali</a>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
    </div>
  </form>
</div>

<?php include "../includes/footer.php"; ?>
