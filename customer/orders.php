<?php
session_start();
include "../config/database.php";
include "../includes/functions.php";
include "../includes/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua orders customer + status payment
$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.status as order_status, o.created_at,
           p.status as payment_status, p.method
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Pesanan Saya</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-5xl mx-auto bg-white p-6 rounded-xl shadow-lg">
    <h1 class="text-2xl font-bold text-blue-600 mb-6 flex items-center gap-2">
      <!-- Icon orders -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M3 7h18M3 12h18M3 17h18" />
      </svg>
      Riwayat Pesanan Saya
    </h1>

    <?php if ($result->num_rows > 0): ?>
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-3 border">No Pesanan</th>
              <th class="p-3 border">Tanggal</th>
              <th class="p-3 border">Total</th>
              <th class="p-3 border">Status Pesanan</th>
              <th class="p-3 border">Status Pembayaran</th>
              <th class="p-3 border">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50">
                <td class="p-3 border font-medium text-blue-600"><?= e($row['order_number']) ?></td>
                <td class="p-3 border"><?= date("d M Y H:i", strtotime($row['created_at'])) ?></td>
                <td class="p-3 border">Rp<?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                <td class="p-3 border">
                  <span class="px-2 py-1 rounded text-sm
                    <?= $row['order_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                    <?= $row['order_status'] === 'paid' ? 'bg-green-100 text-green-700' : '' ?>
                    <?= $row['order_status'] === 'shipped' ? 'bg-blue-100 text-blue-700' : '' ?>
                    <?= $row['order_status'] === 'completed' ? 'bg-gray-200 text-gray-700' : '' ?>
                    <?= $row['order_status'] === 'cancelled' ? 'bg-red-100 text-red-700' : '' ?>">
                    <?= ucfirst($row['order_status']) ?>
                  </span>
                </td>
                <td class="p-3 border">
                  <?php if ($row['payment_status']): ?>
                    <span class="px-2 py-1 rounded text-sm
                      <?= $row['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                      <?= $row['payment_status'] === 'confirmed' ? 'bg-green-100 text-green-700' : '' ?>
                      <?= $row['payment_status'] === 'failed' ? 'bg-red-100 text-red-700' : '' ?>">
                      <?= ucfirst($row['payment_status']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-gray-400">-</span>
                  <?php endif; ?>
                </td>
                <td class="p-3 border">
                  <a href="invoice.php?order_id=<?= $row['id'] ?>" 
                     class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 text-sm">
                    📄 Invoice
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="p-4 bg-yellow-50 border rounded text-yellow-700">
        Kamu belum memiliki riwayat pesanan.
      </div>
    <?php endif; ?>
  </div>
</body>
</html>

<?php include "../includes/footer.php"; ?>
