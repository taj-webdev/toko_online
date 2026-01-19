<?php 
include "../config/database.php"; 
include "../includes/functions.php"; 

if (!isLoggedIn() || userRole() !== 'admin') {
    redirect("login.php");
}
?>
<?php include "../includes/header.php"; ?>

<div class="flex min-h-screen bg-gray-100">

  <!-- Sidebar Mobile (offcanvas) -->
  <aside id="mobileSidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 lg:hidden z-50">
    <div class="p-6 text-2xl font-bold border-b border-gray-700 flex justify-between items-center">
      Admin Panel
      <button onclick="toggleSidebar()" class="text-white">✖</button>
    </div>
    <nav class="p-4 space-y-2">
      <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🏠 Dashboard</a>
      <a href="products.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">📦 Produk</a>
      <a href="categories.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">📂 Kategori</a>
      <a href="orders.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🧾 Pesanan</a>
      <a href="payments.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">💳 Pembayaran</a>
      <a href="shipping.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🚚 Ongkir</a>
      <a href="users.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">👥 Pengguna</a>
    </nav>
    <div class="p-4 border-t border-gray-700">
      <a href="logout.php" class="block bg-red-600 text-center py-2 rounded hover:bg-red-700 transition">🚪 Logout</a>
    </div>
  </aside>

  <!-- Sidebar Desktop -->
  <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-gray-800 text-white fixed inset-y-0 left-0">
    <div class="p-6 text-2xl font-bold border-b border-gray-700">Admin Panel</div>
    <nav class="flex-1 p-4 space-y-2">
      <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🏠 Dashboard</a>
      <a href="products.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">📦 Produk</a>
      <a href="categories.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">📂 Kategori</a>
      <a href="orders.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🧾 Pesanan</a>
      <a href="payments.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">💳 Pembayaran</a>
      <a href="shipping.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">🚚 Ongkir</a>
      <a href="users.php" class="block px-3 py-2 rounded hover:bg-gray-700 transition">👥 Pengguna</a>
    </nav>
    <div class="p-4 border-t border-gray-700">
      <a href="logout.php" class="block bg-red-600 text-center py-2 rounded hover:bg-red-700 transition">🚪 Logout</a>
    </div>
  </aside>

  <!-- Konten Utama -->
  <main class="flex-1 lg:ml-64 p-8">
    <!-- Header Mobile -->
    <div class="lg:hidden flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Dashboard Admin</h1>
      <button onclick="toggleSidebar()" class="text-gray-700 text-2xl">☰</button>
    </div>

    <!-- Header Desktop -->
    <h1 class="hidden lg:block text-3xl font-bold mb-6">Dashboard Admin Toko</h1>

    <!-- Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <a href="products.php" class="bg-blue-600 text-white p-6 rounded-xl shadow hover:bg-blue-700 transition text-center">
        📦 <div class="mt-2 font-semibold">Produk</div>
      </a>
      <a href="categories.php" class="bg-green-600 text-white p-6 rounded-xl shadow hover:bg-green-700 transition text-center">
        📂 <div class="mt-2 font-semibold">Kategori</div>
      </a>
      <a href="orders.php" class="bg-yellow-500 text-white p-6 rounded-xl shadow hover:bg-yellow-600 transition text-center">
        🧾 <div class="mt-2 font-semibold">Pesanan</div>
      </a>
      <a href="payments.php" class="bg-red-600 text-white p-6 rounded-xl shadow hover:bg-red-700 transition text-center">
        💳 <div class="mt-2 font-semibold">Pembayaran</div>
      </a>
      <a href="shipping.php" class="bg-purple-600 text-white p-6 rounded-xl shadow hover:bg-purple-700 transition text-center">
        🚚 <div class="mt-2 font-semibold">Ongkir</div>
      </a>
      <a href="users.php" class="bg-gray-700 text-white p-6 rounded-xl shadow hover:bg-gray-800 transition text-center">
        👥 <div class="mt-2 font-semibold">Pengguna</div>
      </a>
    </div>
  </main>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById('mobileSidebar');
  sidebar.classList.toggle('-translate-x-full');
}
</script>

<?php include "../includes/footer.php"; ?>
