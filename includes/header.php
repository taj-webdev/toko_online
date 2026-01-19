<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wawan Store</title>
  <link href="assets/img/baju.png" rel="icon" type="images/x-icon">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- Navbar -->
<nav class="bg-blue-600 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16 items-center">
      
      <!-- Logo -->
      <div class="flex-shrink-0">
        <a href="/toko_online/index.php" class="text-xl font-bold flex items-center gap-2">
          <img src="/toko_online/assets/img/baju.png" alt="Logo" class="h-8 w-8">
          Wawan Store
        </a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex space-x-6 items-center">
        <a href="/toko_online/customer/cart.php" class="flex items-center gap-1 hover:text-gray-200">
          🛒 <span>Cart</span>
        </a>
        <a href="/toko_online/customer/checkout.php" class="flex items-center gap-1 hover:text-gray-200">
          💳 <span>Checkout</span>
        </a>
        <a href="/toko_online/customer/orders.php" class="flex items-center gap-1 hover:text-gray-200">
          📜 <span>Riwayat</span>
        </a>
        <?php if(isset($_SESSION['user'])): ?>
          <a href="/toko_online/auth/logout.php" class="flex items-center gap-1 hover:text-gray-200">
            🚪 <span>Logout</span>
          </a>
        <?php else: ?>
          <a href="/toko_online/auth/login.php" class="flex items-center gap-1 hover:text-gray-200">
            🔑 <span>Login</span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Hamburger -->
      <div class="md:hidden">
        <button id="mobile-menu-btn" class="focus:outline-none">
          ☰
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-blue-700">
    <a href="/toko_online/customer/cart.php" class="block px-4 py-2 hover:bg-blue-800">🛒 Cart</a>
    <a href="/toko_online/customer/checkout.php" class="block px-4 py-2 hover:bg-blue-800">💳 Checkout</a>
    <a href="/toko_online/customer/orders.php" class="block px-4 py-2 hover:bg-blue-800">📜 Riwayat</a>
    <?php if(isset($_SESSION['user'])): ?>
      <a href="/toko_online/auth/logout.php" class="block px-4 py-2 hover:bg-blue-800">🚪 Logout</a>
    <?php else: ?>
      <a href="/toko_online/auth/login.php" class="block px-4 py-2 hover:bg-blue-800">🔑 Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container mx-auto p-4">

<script>
  // Toggle Mobile Menu
  document.addEventListener("DOMContentLoaded", function() {
    const btn = document.getElementById("mobile-menu-btn");
    const menu = document.getElementById("mobile-menu");
    btn.addEventListener("click", () => {
      menu.classList.toggle("hidden");
    });
  });
</script>
