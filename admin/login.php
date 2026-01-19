<?php 
include "../config/database.php"; 
include "../includes/functions.php"; 

// Kalau sudah login sebagai admin, langsung redirect ke dashboard
if (isLoggedIn() && userRole() === 'admin') {
    redirect("index.php");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user'] = $admin;
        redirect("index.php");
    } else {
        $error = "Login admin gagal! Cek email atau password.";
    }
}
?>
<?php include "../includes/header.php"; ?>

<div class="flex justify-center items-center min-h-screen bg-gradient-to-br from-gray-800 to-gray-900 px-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 sm:p-8 w-full max-w-md">
    <div class="flex flex-col items-center mb-6">
      <div class="bg-blue-600 text-white w-14 h-14 flex items-center justify-center rounded-full mb-3">
        <!-- Heroicon: Shield Check -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-800 text-center">Login Panel Admin</h2>
      <p class="text-gray-500 text-sm mt-1">Masuk untuk mengelola toko</p>
    </div>

    <?php if ($error): ?>
      <p class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <!-- Email -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Email</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <!-- Heroicon: Mail -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 0l-4-4m0 0l-4 4m4-4v12" />
            </svg>
          </span>
          <input type="email" name="email" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" 
            placeholder="admin@toko.com">
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <!-- Heroicon: Lock Closed -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M12 11c.828 0 1.5-.672 1.5-1.5S12.828 8 12 8s-1.5.672-1.5 1.5S11.172 11 12 11zm-6 8h12V11H6v8z" />
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
        <!-- Heroicon: Login -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M11 16l-4-4m0 0l4-4m-4 4h12m-4 4v1a2 2 0 01-2 2h-4a2 2 0 01-2-2v-1" />
        </svg>
        Login
      </button>
    </form>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
