<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

// ambil barang aktif
$data = $conn->query("SELECT * FROM items WHERE is_active = 1");

// buat session keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Barang</title>
    <style>
        body { background: #f4f4f4; font-family: Arial; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            transition: 0.2s;
        }
        .card:hover { transform: scale(1.02); }
        .card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            background: #ddd;
        }
        .card-body { padding: 15px; }
        .btn {
            padding: 8px 14px;
            background: #0d6efd;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            margin-top: 10px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }

        /* POPUP */
        #qtyPopup {
            position: fixed;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
        }
        #popupBox {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }
        input[type=number] {
            padding: 7px;
            width: 80px;
            margin-top: 10px;
        }
    </style>

    <script>
        let selectedItemId = null;
        let maxStock = 0;

        function openQtyPopup(id, stock) {
            selectedItemId = id;
            maxStock = stock;
            document.getElementById("qty").value = 1;
            document.getElementById("qty").max = stock;
            document.getElementById("maxStock").innerText = stock;
            document.getElementById("qtyPopup").style.display = "flex";
        }

        function submitQty() {
            let qty = document.getElementById("qty").value;

            if (qty < 1) qty = 1;
            if (qty > maxStock) qty = maxStock;

            window.location.href = "cart_add.php?id=" + selectedItemId + "&qty=" + qty;
        }

        function closePopup() {
            document.getElementById("qtyPopup").style.display = "none";
        }
    </script>
</head>
<body>

<div class="container">

    <div class="topbar">
        <h2>Daftar Barang</h2>
        <a href="cart.php" class="btn">Keranjang (<?= count($_SESSION['cart']) ?>)</a>
    </div>

    <div class="grid">

        <?php while($b = $data->fetch_assoc()): ?>
            <div class="card">
                <img src="../gambar_item/<?= htmlspecialchars($b['image']) ?>" alt="Foto Barang">

                <div class="card-body">
                    <div><b><?= htmlspecialchars($b['name']) ?></b></div>

                    <div>Kategori: <?= htmlspecialchars($b['category']) ?></div>
                    <div>Tipe: <?= htmlspecialchars($b['type']) ?></div>
                    <div>Stok: <?= $b['stock'] ?></div>

                    <button class="btn" 
                        onclick="openQtyPopup(<?= $b['id'] ?>, <?= $b['stock'] ?>)">
                        Tambah ke Keranjang
                    </button>
                </div>
            </div>
        <?php endwhile ?>

    </div>
</div>

<!-- POPUP INPUT JUMLAH -->
<div id="qtyPopup">
    <div id="popupBox">
        <h3>Pilih Jumlah</h3>
        <p>Stok tersedia: <b id="maxStock"></b></p>

        <input type="number" id="qty" min="1" value="1">

        <br><br>

        <button class="btn" onclick="submitQty()">Tambah</button>
        <button class="btn" style="background:#dc3545" onclick="closePopup()">Batal</button>
    </div>
</div>

</body>
</html>
