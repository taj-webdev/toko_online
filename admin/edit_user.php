<?php
include "../config/database.php";
include "../includes/functions.php";

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("../auth/login.php");
}

if (!isset($_GET['id'])) {
    redirect("users.php");
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    redirect("users.php");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role  = $_POST['role'];

    if (!$name || !$email) {
        $errors[] = "Nama dan email wajib diisi.";
    } else {
        // cek email unik, tapi abaikan user yg sedang di-edit
        $check = $conn->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "Email sudah dipakai oleh pengguna lain.";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?,email=?,phone=?,role=?,password=? WHERE id=?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $password, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?,email=?,phone=?,role=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);
            }
            if ($stmt->execute()) {
                redirect("users.php");
            } else {
                $errors[] = "Gagal update pengguna: " . $conn->error;
            }
        }
    }
}

include "../includes/header.php";
?>

<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md mt-6">
  <h2 class="text-xl font-bold mb-4">✏️ Edit Pengguna</h2>

  <?php if ($errors): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
      <?= implode("<br>", $errors) ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block mb-1 font-medium">Nama</label>
      <input type="text" name="name" class="w-full border rounded px-3 py-2" value="<?= e($user['name']) ?>" required>
    </div>
    <div>
      <label class="block mb-1 font-medium">Email</label>
      <input type="email" name="email" class="w-full border rounded px-3 py-2" value="<?= e($user['email']) ?>" required>
    </div>
    <div>
      <label class="block mb-1 font-medium">No HP</label>
      <input type="text" name="phone" class="w-full border rounded px-3 py-2" value="<?= e($user['phone']) ?>">
    </div>
    <div>
      <label class="block mb-1 font-medium">Password (kosongkan jika tidak diganti)</label>
      <input type="password" name="password" class="w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="block mb-1 font-medium">Role</label>
      <select name="role" class="w-full border rounded px-3 py-2">
        <option value="customer" <?= $user['role']==='customer'?'selected':'' ?>>Customer</option>
        <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
      </select>
    </div>
    <div class="flex justify-between">
      <a href="users.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">⬅ Kembali</a>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
    </div>
  </form>
</div>

<?php include "../includes/footer.php"; ?>
