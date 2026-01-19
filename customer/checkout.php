<?php
// customer/checkout.php
session_start();
include "../config/database.php";
include "../includes/functions.php"; // asumsi ada e(), rupiah(), isLoggedIn(), redirect()

// pastikan user login
if (!isset($_SESSION['user_id'])) {
    // redirect ke auth login (sesuai proyek kamu)
    redirect("../auth/login.php");
}

// pastikan ada cart
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    redirect("../index.php");
}

// ambil daftar provinsi awal
$provinces = $conn->query("SELECT id, name FROM provinces ORDER BY name ASC");

// ambil user data
$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// prepare cart items
$cartItems = [];
$totalBelanja = 0.0;
if (!empty($_SESSION['cart'])) {
    $idsArr = array_map('intval', array_keys($_SESSION['cart']));
    // safety: jika kosong skip
    if (count($idsArr) > 0) {
        $ids = implode(',', $idsArr);
        $sql = "SELECT id, name, price, image, stock FROM products WHERE id IN ($ids)";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $r['qty'] = $_SESSION['cart'][$r['id']] ?? 0;
            $r['subtotal'] = $r['qty'] * $r['price'];
            $cartItems[] = $r;
            $totalBelanja += $r['subtotal'];
        }
    }
}

$msg = "";
$err = "";

// Handle form submission
// ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // get & basic validation
    $recipient_name = trim($_POST['recipient_name'] ?? $user['name']);
    $phone = trim($_POST['phone'] ?? $user['phone']);
    $email = trim($_POST['email'] ?? $user['email']);
    $detail = trim($_POST['address'] ?? '');
    $province_id = (int)($_POST['province_id'] ?? 0);
    $regency_id = (int)($_POST['regency_id'] ?? 0);
    $postal_code = trim($_POST['postal_code'] ?? '');
    $shipping_rate_id = (int)($_POST['shipping_rate_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'transfer';

    if ($recipient_name === '' || $phone === '' || $detail === '' || $province_id <= 0 || $regency_id <= 0 || $shipping_rate_id <= 0) {
        $err = "Harap lengkapi semua field pengiriman dan pilih kurir.";
    } elseif (empty($cartItems)) {
        $err = "Keranjang kosong.";
    } else {
        // ambil ongkir & estimasi
        $shipStmt = $conn->prepare("SELECT id, courier, cost, estimated_days FROM shipping_rates WHERE id = ? LIMIT 1");
        $shipStmt->bind_param("i", $shipping_rate_id);
        $shipStmt->execute();
        $ship = $shipStmt->get_result()->fetch_assoc();
        $shipStmt->close();

        if (!$ship) {
            $err = "Data ongkir tidak ditemukan.";
        } else {
            $shippingCost = (float)$ship['cost'];
            $grandTotal = $totalBelanja + $shippingCost;

            $conn->begin_transaction();

            try {
                // 1) simpan address
                $insAddr = $conn->prepare("INSERT INTO addresses (user_id, province_id, regency_id, detail, postal_code) VALUES (?,?,?,?,?)");
                $insAddr->bind_param("iiiss", $userId, $province_id, $regency_id, $detail, $postal_code);
                if (!$insAddr->execute()) throw new Exception("Gagal menyimpan alamat: " . $insAddr->error);
                $address_id = $insAddr->insert_id;
                $insAddr->close();

                // 2) generate nomor order (misal INV-20250917-1234)
                $order_number = "INV-" . date("Ymd") . "-" . rand(1000,9999);

                // 3) simpan order
                $insOrder = $conn->prepare("INSERT INTO orders (order_number, user_id, address_id, total_amount, status) VALUES (?,?,?,?, 'pending')");
                $insOrder->bind_param("siid", $order_number, $userId, $address_id, $grandTotal);
                if (!$insOrder->execute()) throw new Exception("Gagal membuat pesanan: " . $insOrder->error);
                $order_id = $insOrder->insert_id;
                $insOrder->close();

                // 4) simpan order_items
                $insItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
                foreach ($cartItems as $it) {
                    $pid = (int)$it['id'];
                    $qty = (int)$it['qty'];
                    $price = (float)$it['price'];
                    $insItem->bind_param("iiid", $order_id, $pid, $qty, $price);
                    if (!$insItem->execute()) throw new Exception("Gagal simpan item: " . $insItem->error);
                }
                $insItem->close();

                // 5) simpan payment
                $payStatus = ($payment_method === 'cod') ? 'confirmed' : 'pending';
                $insPay = $conn->prepare("INSERT INTO payments (order_id, method, amount, status) VALUES (?,?,?,?)");
                $insPay->bind_param("isds", $order_id, $payment_method, $grandTotal, $payStatus);
                if (!$insPay->execute()) throw new Exception("Gagal simpan pembayaran: " . $insPay->error);
                $insPay->close();

                $conn->commit();

                $_SESSION['cart'] = [];

                header("Location: invoice.php?order_id=" . $order_id);
                exit;
            } catch (Exception $ex) {
                $conn->rollback();
                $err = $ex->getMessage();
            }
        }
    }
}

// render form
include "../includes/header.php";
?>
<div class="container mx-auto p-4 max-w-6xl">
  <div class="bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-3">
      <!-- icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-6-9v9" />
      </svg>
      Checkout
    </h1>

    <?php if ($err): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h2 class="font-semibold mb-3">Informasi Pengiriman</h2>

        <label class="block text-sm mb-1">Nama Penerima</label>
        <input type="text" name="recipient_name" value="<?= e($recipient_name ?? $user['name']) ?>" required class="w-full border rounded px-3 py-2 mb-3">

        <label class="block text-sm mb-1">No. HP</label>
        <input type="text" name="phone" value="<?= e($phone ?? $user['phone']) ?>" required class="w-full border rounded px-3 py-2 mb-3">

        <label class="block text-sm mb-1">Email</label>
        <input type="email" name="email" value="<?= e($email ?? $user['email']) ?>" required class="w-full border rounded px-3 py-2 mb-3">

        <label class="block text-sm mb-1">Alamat Lengkap</label>
        <textarea name="address" required class="w-full border rounded px-3 py-2 mb-3"><?= e($detail ?? '') ?></textarea>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-sm mb-1">Provinsi</label>
            <select id="province_select" name="province_id" class="w-full border rounded px-3 py-2">
              <option value="">-- Pilih Provinsi --</option>
              <?php while ($p = $provinces->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">Kabupaten / Kota</label>
            <select id="regency_select" name="regency_id" class="w-full border rounded px-3 py-2">
              <option value="">-- Pilih Kabupaten/Kota --</option>
            </select>
          </div>
        </div>

        <label class="block text-sm mb-1">Kode Pos</label>
        <input type="text" name="postal_code" id="postal_code" readonly class="w-full border rounded px-3 py-2 mb-3 bg-gray-50">

        <label class="block text-sm mb-1">Kurir / Layanan</label>
        <select id="shipping_select" name="shipping_rate_id" required class="w-full border rounded px-3 py-2 mb-3">
          <option value="">-- Pilih Kurir --</option>
        </select>

        <div id="shipping_info" class="text-sm text-gray-700 mb-3 hidden">
          Ongkir: <span id="ship_cost" class="font-semibold"></span> • Estimasi: <span id="ship_eta"></span>
        </div>

        <label class="block text-sm mb-1">Metode Pembayaran</label>
        <select name="payment_method" required class="w-full border rounded px-3 py-2 mb-3">
          <option value="transfer">Transfer Bank</option>
          <option value="ewallet">E-Wallet</option>
          <option value="cod">COD (Bayar di Tempat)</option>
        </select>
      </div>

      <div>
        <h2 class="font-semibold mb-3">Ringkasan Pesanan</h2>
        <div class="bg-gray-50 p-4 rounded mb-4">
          <?php if (!empty($cartItems)): ?>
            <?php foreach ($cartItems as $ci): ?>
              <div class="flex justify-between border-b py-2">
                <div>
                  <div class="font-medium"><?= e($ci['name']) ?> <span class="text-xs text-gray-500">x<?= (int)$ci['qty'] ?></span></div>
                  <div class="text-xs text-gray-500"><?= rupiah($ci['price']) ?> / pcs</div>
                </div>
                <div class="font-semibold"><?= rupiah($ci['subtotal']) ?></div>
              </div>
            <?php endforeach; ?>
            <div class="flex justify-between font-semibold mt-3">
              <div>Total Belanja</div>
              <div id="subtotal_text"><?= rupiah($totalBelanja) ?></div>
            </div>
            <div class="flex justify-between mt-2">
              <div>Ongkir</div>
              <div id="shipping_text"><?= rupiah(0) ?></div>
            </div>
            <div class="flex justify-between font-bold text-lg mt-3">
              <div>Grand Total</div>
              <div id="grandtotal_text"><?= rupiah($totalBelanja) ?></div>
            </div>
          <?php else: ?>
            <p class="text-gray-600">Keranjang kosong.</p>
          <?php endif; ?>
        </div>

        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
          Buat Pesanan & Lanjut ke Invoice
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
  const provinceSelect = document.getElementById('province_select');
  const regencySelect = document.getElementById('regency_select');
  const postalInput = document.getElementById('postal_code');
  const shippingSelect = document.getElementById('shipping_select');
  const shipCostEl = document.getElementById('ship_cost');
  const shipEtaEl = document.getElementById('ship_eta');
  const shippingInfo = document.getElementById('shipping_info');
  const shippingText = document.getElementById('shipping_text');
  const grandText = document.getElementById('grandtotal_text');
  const subtotal = <?= json_encode((float)$totalBelanja) ?>;

  provinceSelect.addEventListener('change', function(){
    const pid = this.value;
    regencySelect.innerHTML = '<option>Loading...</option>';
    postalInput.value = '';
    shippingSelect.innerHTML = '<option value="">-- Pilih Kurir --</option>';
    shippingInfo.classList.add('hidden');
    if (!pid) {
      regencySelect.innerHTML = '<option value="">-- Pilih Kabupaten/Kota --</option>';
      return;
    }
    fetch('get_regencies.php?province_id='+encodeURIComponent(pid))
      .then(r=>r.json())
      .then(data=>{
        let html = '<option value="">-- Pilih Kabupaten/Kota --</option>';
        data.forEach(function(r){
          html += `<option data-postal="${r.postal_code}" value="${r.id}">${r.name} (${r.postal_code||'-'})</option>`;
        });
        regencySelect.innerHTML = html;
      }).catch(()=>regencySelect.innerHTML = '<option value="">-- Pilih Kabupaten/Kota --</option>');
  });

  regencySelect.addEventListener('change', function(){
    const rid = this.value;
    postalInput.value = '';
    shippingSelect.innerHTML = '<option value="">Loading...</option>';
    shippingInfo.classList.add('hidden');
    if (!rid) {
      shippingSelect.innerHTML = '<option value="">-- Pilih Kurir --</option>';
      return;
    }
    // fill postal from selected option data attribute (if available)
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset && opt.dataset.postal) postalInput.value = opt.dataset.postal;

    // fetch shipping rates
    fetch('get_shipping_rates.php?regency_id='+encodeURIComponent(rid))
      .then(r=>r.json())
      .then(data=>{
        let html = '<option value="">-- Pilih Kurir --</option>';
        data.forEach(function(s){
          html += `<option value="${s.id}" data-cost="${s.cost}" data-est="${s.estimated_days}">${s.courier} — Rp ${Number(s.cost).toLocaleString()} (${s.estimated_days})</option>`;
        });
        shippingSelect.innerHTML = html;
      }).catch(()=> shippingSelect.innerHTML = '<option value="">-- Pilih Kurir --</option>');
  });

  shippingSelect.addEventListener('change', function(){
    const idx = this.selectedIndex;
    const opt = this.options[idx];
    if (opt && opt.dataset && opt.dataset.cost) {
      const cost = parseFloat(opt.dataset.cost) || 0;
      const est = opt.dataset.est || '';
      shipCostEl.textContent = new Intl.NumberFormat('id-ID',{style:'currency', currency:'IDR'}).format(cost);
      shipEtaEl.textContent = est;
      shippingInfo.classList.remove('hidden');
      shippingText.textContent = new Intl.NumberFormat('id-ID',{style:'currency', currency:'IDR'}).format(cost);
      grandText.textContent = new Intl.NumberFormat('id-ID',{style:'currency', currency:'IDR'}).format(subtotal + cost);
    } else {
      shippingInfo.classList.add('hidden');
      shippingText.textContent = new Intl.NumberFormat('id-ID',{style:'currency', currency:'IDR'}).format(0);
      grandText.textContent = new Intl.NumberFormat('id-ID',{style:'currency', currency:'IDR'}).format(subtotal);
    }
  });
});
</script>

<?php include "../includes/footer.php"; ?>
