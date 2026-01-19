<?php 
include "../config/database.php"; 
include "../includes/functions.php"; 

if (isLoggedIn()) {
    redirect("../index.php");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Password tidak sama!";
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'customer')");
        $stmt->bind_param("sss", $name, $email, $hashed);
        if ($stmt->execute()) {
            $success = "Pendaftaran berhasil, silakan login!";
        } else {
            $error = "Email sudah digunakan atau terjadi error!";
        }
    }
}
?>
<?php include "../includes/header.php"; ?>

<div class="flex justify-center items-center min-h-screen bg-gradient-to-br from-green-50 to-green-100 px-4">
  <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 w-full max-w-md">
    <div class="flex flex-col items-center mb-6">
      <div class="bg-green-600 text-white w-12 h-12 flex items-center justify-center rounded-full mb-3">
        <!-- Heroicon: User Add -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M13 7a4 4 0 11-8 0 4 4 0 018 0zM6 21v-2a4 4 0 014-4h0a4 4 0 014 4v2" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-800">Daftar Akun Baru</h2>
    </div>

    <?php if($error): ?>
      <p class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if($success): ?>
      <p class="bg-green-100 text-green-600 p-3 rounded mb-4 text-sm"><?= e($success) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <!-- Nama -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Nama Lengkap</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <!-- Heroicon: User -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 0112 15a4 4 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </span>
          <input type="text" name="name" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
            placeholder="Nama lengkap">
        </div>
      </div>

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
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
            placeholder="you@example.com">
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <!-- Heroicon: Key -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-2-2a2 2 0 100 4m0-4v10m-2-4h-2m4 0h2" />
            </svg>
          </span>
          <input type="password" name="password" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
            placeholder="••••••••">
        </div>
      </div>

      <!-- Konfirmasi Password -->
      <div>
        <label class="block mb-1 text-gray-700 font-medium">Konfirmasi Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <!-- Heroicon: Check -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </span>
          <input type="password" name="confirm" required 
            class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
            placeholder="Ulangi password">
        </div>
      </div>

      <!-- Tombol -->
      <button type="submit" 
        class="w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
        <!-- Heroicon: Check Circle -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" />
        </svg>
        Daftar
      </button>

      <p class="text-center text-sm mt-4 text-gray-600">
        Sudah punya akun? 
        <a href="login.php" class="text-green-600 hover:underline">Login</a>
      </p>
    </form>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
