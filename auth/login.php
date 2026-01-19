<?php 
session_start();
include "../config/database.php"; 
include "../includes/functions.php"; 

// Jika sudah login, redirect ke home (atau ke redirect tujuan kalau ada)
if (isLoggedIn()) {
    if (!empty($_GET['redirect'])) {
        redirect($_GET['redirect']);
    } else {
        redirect("../index.php");
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Simpan session user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user; // kalau mau akses detail user
        $_SESSION['role'] = $user['role'] ?? 'customer';

        // Redirect ke tujuan kalau ada
        if (!empty($_GET['redirect'])) {
            redirect($_GET['redirect']);
        } else {
            redirect("../index.php");
        }
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<?php include "../includes/header.php"; ?>

<div class="flex justify-center items-center min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 px-4">
  <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 w-full max-w-md">
    <div class="flex flex-col items-center mb-6">
      <div class="bg-blue-600 text-white w-12 h-12 flex items-center justify-center rounded-full mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6-2v2m12-2v2m-6-8a4 4 0 00-8 0v2a4 4 0 008 0V9z" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-800">Login ke Akun Anda</h2>
    </div>

    <?php if($error): ?>
      <p class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input type="hidden" name="redirect" value="<?= e($_GET['redirect'] ?? '') ?>">

      <!-- Email -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Email</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 0l-4-4m0 0l-4 4m4-4v12" />
            </svg>
          </span>
          <input type="email" name="email" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
            placeholder="you@example.com">
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-2-2a2 2 0 100 4m0-4v10m-2-4h-2m4 0h2" />
            </svg>
          </span>
          <input type="password" name="password" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
            placeholder="••••••••">
        </div>
      </div>

      <!-- Tombol -->
      <button type="submit" 
        class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H3m12 0l-4-4m4 4l-4 4" />
        </svg>
        Login
      </button>

      <p class="text-center text-sm mt-4 text-gray-600">
        Belum punya akun? 
        <a href="register.php" class="text-blue-600 hover:underline">Daftar</a>
      </p>
    </form>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
