<?php
session_start();
include "../config/database.php";
include "../includes/functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Ambil detail order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order tidak ditemukan atau bukan milik Anda.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method_input = $_POST['method'] ?? '';
    $status = "pending";
    $proof_file = null;

    // mapping metode dari form ke enum DB
    $method_map = [
        "Transfer Bank" => "transfer",
        "E-Wallet" => "ewallet",
        "COD" => "cod"
    ];
    $method = $method_map[$method_input] ?? "transfer";

    // COD langsung confirmed
    if ($method === "cod") {
        $status = "confirmed";
    } else {
        // Upload bukti transfer/ewallet
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
            $targetDir = __DIR__ . "/../assets/uploads/payments/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
            $filename = "proof_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $targetFile = $targetDir . $filename;

            if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetFile)) {
                $proof_file = $filename; // simpan hanya nama file ke DB
            }
        }
    }

    // Cek apakah payment untuk order ini sudah ada
    $check = $conn->prepare("SELECT id FROM payments WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
        // Update payment
        $sql = "UPDATE payments SET method=?, status=?, proof=IFNULL(?, proof), paid_at=NOW() WHERE order_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $method, $status, $proof_file, $order_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert payment baru
        $sql = "INSERT INTO payments (order_id, method, proof, status, created_at, paid_at) VALUES (?,?,?,?,NOW(),NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $order_id, $method, $proof_file, $status);
        $stmt->execute();
        $stmt->close();
    }

    // Update status order
    if ($method === "cod") {
        $conn->query("UPDATE orders SET status='paid' WHERE id=$order_id");
    } elseif ($method === "transfer" || $method === "ewallet") {
        $conn->query("UPDATE orders SET status='pending' WHERE id=$order_id");
    }

    $message = "Konfirmasi pembayaran berhasil dikirim.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Konfirmasi Pembayaran</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow-md">
    <h1 class="text-2xl font-bold text-blue-600 mb-4 flex items-center gap-2">
      <!-- Ikon invoice -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2h6v2m-6-4v-2h6v2M9 9V7h6v2M5 5h14a2 2 0 012 2v12l-4-2-4 2-4-2-4 2V7a2 2 0 012-2z" />
      </svg>
      Konfirmasi Pembayaran
    </h1>

    <?php if ($message): ?>
      <div class="p-3 mb-4 text-green-800 bg-green-100 rounded-lg"><?= $message; ?></div>
    <?php endif; ?>

    <p class="mb-4 text-gray-700">
      Order: <span class="font-semibold">#<?= $order['order_number'] ?? $order['id']; ?></span>
    </p>

    <!-- Info Rekening -->
    <div class="mb-6 space-y-3">
      <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 13l1.5 1.5a2 2 0 002.828 0L12 11.828l2.672 2.672a2 2 0 002.828 0L19 13m-7 8a9 9 0 100-18 9 9 0 000 18z" />
        </svg>
        <div>
          <p class="font-medium">Transfer Bank</p>
          <p class="text-sm text-gray-600">BRIVA An. Wawan Suharmanto</p>
          <p class="text-sm text-gray-600">BRI : 109530100025287</p>
          <p class="text-sm text-gray-600">BNI : 8510880100025287</p>
          <p class="text-sm text-gray-600">BCA : 816100100025287</p>
          <p class="text-sm text-gray-600">MANDIRI : 885880100025287</p>
        </div>
      </div>

      <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .843-3 1.875S10.343 11.75 12 11.75s3 .843 3 1.875S13.657 15.5 12 15.5s-3-.843-3-1.875M12 8V7m0 8v1m-6 2a9 9 0 1112 0" />
        </svg>
        <div>
          <p class="font-medium">E-Wallet</p>
          <p class="text-sm text-gray-600">DANA / OVO / GOPAY : 082251373438 a.n Wawan Suharmanto</p>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block mb-1 font-medium">Metode Pembayaran</label>
        <select name="method" id="method" class="w-full border rounded p-2">
          <option value="Transfer Bank">Transfer Bank</option>
          <option value="E-Wallet">E-Wallet</option>
          <option value="COD">Cash on Delivery</option>
        </select>
      </div>

      <div id="proofUpload" class="hidden">
        <label class="block mb-1 font-medium">Upload Bukti Pembayaran</label>
        <input type="file" name="proof" class="w-full border rounded p-2">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        Kirim Konfirmasi
      </button>
    </form>
  </div>

  <script>
    const methodSelect = document.getElementById('method');
    const proofUpload = document.getElementById('proofUpload');

    function toggleProof() {
      if (methodSelect.value === 'Transfer Bank' || methodSelect.value === 'E-Wallet') {
        proofUpload.classList.remove('hidden');
      } else {
        proofUpload.classList.add('hidden');
      }
    }

    methodSelect.addEventListener('change', toggleProof);
    toggleProof();
  </script>
</body>
</html>
