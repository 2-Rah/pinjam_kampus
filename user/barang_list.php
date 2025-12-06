<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// ambil barang aktif
$data = $conn->query("SELECT * FROM items WHERE is_active = 1");

// buat session keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Barang - Sistem Peminjaman</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
    color: #1a202c;
}

/* NAVBAR */
.navbar {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(15px);
    padding: 16px 32px;
    border-radius: 16px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.navbar-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
}

.navbar-title svg {
    width: 28px;
    height: 28px;
    fill: white;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 15px;
    font-weight: 500;
}

.back-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: .3s;
    box-shadow: 0 4px 10px rgba(102,126,234,0.4);
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.cart-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: .3s;
    box-shadow: 0 4px 10px rgba(102,126,234,0.4);
    display: flex;
    align-items: center;
    gap: 8px;
}

.cart-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.cart-badge {
    background: #ff5722;
    color: white;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

/* CONTAINER */
.container {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

/* HEADER */
.page-header {
    background: white;
    padding: 28px 32px;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    margin-bottom: 35px;
    text-align: center;
    animation: fadeIn 0.6s ease-out;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #1a202c;
}

.page-header p {
    color: #64748b;
    font-size: 16px;
    max-width: 600px;
    margin: 0 auto;
}

/* ITEMS GRID */
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.item-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    transition: 0.3s ease;
    animation: fadeIn 0.6s ease-out;
}

.item-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.item-image-container {
    width: 100%;
    height: 180px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.item-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.item-card:hover .item-image {
    transform: scale(1.05);
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.image-placeholder svg {
    width: 64px;
    height: 64px;
    fill: white;
    opacity: 0.8;
    margin-bottom: 12px;
}

.item-body {
    padding: 24px;
}

.item-name {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1a202c;
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.item-detail {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 14px;
}

.item-detail svg {
    width: 16px;
    height: 16px;
    fill: #667eea;
    flex-shrink: 0;
}

.capacity-info {
    background: #f0f4ff;
    padding: 12px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.capacity-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.capacity-number {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
}

.capacity-unit {
    font-size: 14px;
    color: #667eea;
    font-weight: 500;
    margin-left: 4px;
}

.add-to-cart-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.add-to-cart-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.add-to-cart-btn:disabled {
    background: #e0e0e0;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* CONFIRMATION POPUP */
.popup-overlay {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(5px);
}

.popup-content {
    background: white;
    padding: 32px;
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: popupIn 0.3s ease-out;
}

.popup-header {
    margin-bottom: 20px;
}

.popup-header h3 {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 8px;
}

.popup-header p {
    color: #64748b;
    font-size: 14px;
}

.popup-info {
    background: #f0f4ff;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    text-align: left;
}

.popup-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.popup-info-label {
    color: #64748b;
}

.popup-info-value {
    color: #1a202c;
    font-weight: 500;
}

.popup-buttons {
    display: flex;
    gap: 16px;
}

.popup-btn {
    flex: 1;
    padding: 16px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s ease;
}

.popup-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.popup-btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.popup-btn-secondary {
    background: #f0f4ff;
    color: #667eea;
}

.popup-btn-secondary:hover {
    background: #e0e8ff;
    transform: translateY(-2px);
}

/* ITEM QUANTITY POPUP (for items only) */
.quantity-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 32px;
}

.quantity-btn {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #f0f4ff;
    border: none;
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
    cursor: pointer;
    transition: 0.2s;
}

.quantity-btn:hover {
    background: #e0e8ff;
}

.quantity-input {
    width: 80px;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
}

.quantity-input:focus {
    outline: none;
    border-color: #667eea;
}

.stock-display {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-weight: 600;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    grid-column: 1 / -1;
}

.empty-state svg {
    width: 80px;
    height: 80px;
    fill: #e0e0e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 22px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 10px;
}

.empty-state p {
    color: #64748b;
    font-size: 16px;
}

/* ANIMATIONS */
@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes popupIn {
    from { 
        opacity: 0; 
        transform: scale(0.9); 
    }
    to { 
        opacity: 1; 
        transform: scale(1); 
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 20px;
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .container {
        padding: 0 10px;
    }

    .page-header {
        padding: 20px;
        margin-bottom: 25px;
    }

    .items-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }

    .item-body {
        padding: 20px;
    }

    .popup-content {
        padding: 24px;
        margin: 20px;
    }

    .popup-buttons {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .items-grid {
        grid-template-columns: 1fr;
    }
    
    .item-card {
        max-width: 100%;
    }
}
</style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-title">
            <svg viewBox="0 0 24 24">
                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>
            </svg>
            <span>Peminjaman Barang</span>
        </div>

        <div class="user-info">
            <a href="user_dashboard.php" class="back-btn">
                <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: white;">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                Dashboard
            </a>
            <a href="cart.php" class="cart-btn">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: white;">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                Keranjang
                <span class="cart-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container">

        <!-- HEADER -->
        <div class="page-header">
            <h1>Daftar Barang dan Ruangan Tersedia</h1>
            <p>Pilih barang atau ruangan yang ingin Anda pinjam dan tambahkan ke keranjang</p>
        </div>

        <!-- ITEMS GRID -->
        <div class="items-grid">
            <?php if($data->num_rows > 0): ?>
                <?php while($item = $data->fetch_assoc()): ?>
                    <?php 
                    // Check if item is a room (has capacity)
                    $is_room = isset($item['capacity']) && $item['capacity'] > 0;
                    $available = $is_room ? true : ($item['stock'] > 0);
                    ?>
                    <div class="item-card" data-id="<?= $item['id'] ?>">
                        <div class="item-image-container">
                            <?php if(!empty($item['image']) && file_exists("../gambar_item/" . $item['image'])): ?>
                                <img src="../gambar_item/<?= htmlspecialchars($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="item-image">
                            <?php else: ?>
                                <div class="image-placeholder">
                                    <svg viewBox="0 0 24 24">
                                        <?php if($is_room): ?>
                                            <!-- Icon for rooms -->
                                            <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                                        <?php else: ?>
                                            <!-- Icon for items -->
                                            <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                        <?php endif; ?>
                                    </svg>
                                    <span><?= $is_room ? 'Ruangan' : 'Barang' ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-body">
                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                            
                            <div class="item-details">
                                <div class="item-detail">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                    </svg>
                                    <span>Kategori: <?= htmlspecialchars($item['category']) ?></span>
                                </div>
                                <div class="item-detail">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <span>Tipe: <?= htmlspecialchars($item['type']) ?></span>
                                </div>
                            </div>
                            
                            <div class="capacity-info">
                                <div class="capacity-label">
                                    <?= $is_room ? 'Kapasitas Ruangan' : 'Stok Tersedia' ?>
                                </div>
                                <div class="capacity-number">
                                    <?= $is_room ? $item['capacity'] : $item['stock'] ?>
                                    <?php if($is_room): ?>
                                        <span class="capacity-unit">orang</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if($is_room): ?>
                                <!-- Tombol untuk ruangan (langsung tambah tanpa jumlah) -->
                                <button class="add-to-cart-btn" 
                                        onclick="addRoomToCart(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['capacity'] ?>)"
                                        <?= !$available ? 'disabled' : '' ?>>
                                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: white;">
                                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                    </svg>
                                    <?= $available ? 'Tambah ke Keranjang' : 'Tidak Tersedia' ?>
                                </button>
                            <?php else: ?>
                                <!-- Tombol untuk barang (butuh jumlah) -->
                                <button class="add-to-cart-btn" 
                                        onclick="openItemQtyPopup(<?= $item['id'] ?>, <?= $item['stock'] ?>, '<?= htmlspecialchars($item['name']) ?>')"
                                        <?= !$available ? 'disabled' : '' ?>>
                                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: white;">
                                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                    </svg>
                                    <?= $available ? 'Tambah ke Keranjang' : 'Stok Habis' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/>
                    </svg>
                    <h3>Tidak ada barang tersedia</h3>
                    <p>Silakan coba lagi nanti atau hubungi administrator</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- POPUP KONFIRMASI RUANGAN -->
    <div id="roomConfirmationPopup" class="popup-overlay" onclick="closeRoomPopup(event)">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Konfirmasi Peminjaman Ruangan</h3>
                <p>Anda akan meminjam ruangan berikut:</p>
            </div>
            
            <div class="popup-info">
                <div class="popup-info-item">
                    <span class="popup-info-label">Nama Ruangan:</span>
                    <span class="popup-info-value" id="popupRoomName"></span>
                </div>
                <div class="popup-info-item">
                    <span class="popup-info-label">Kapasitas:</span>
                    <span class="popup-info-value">
                        <span id="popupRoomCapacity"></span> orang
                    </span>
                </div>
                <div class="popup-info-item">
                    <span class="popup-info-label">Jumlah:</span>
                    <span class="popup-info-value">1 ruangan</span>
                </div>
            </div>
            
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-primary" onclick="confirmAddRoom()">
                    Ya, Tambahkan
                </button>
                <button class="popup-btn popup-btn-secondary" onclick="closeRoomPopup()">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- POPUP INPUT JUMLAH BARANG -->
    <div id="itemQtyPopup" class="popup-overlay" onclick="closeItemPopup(event)">
        <div class="popup-content">
            <div class="popup-header">
                <h3 id="popupItemName"></h3>
                <p>Masukkan jumlah yang ingin dipinjam</p>
            </div>
            
            <div class="stock-display">
                Stok tersedia: <span id="maxStock">0</span> unit
            </div>
            
            <div class="quantity-control">
                <button class="quantity-btn" onclick="changeItemQty(-1)">−</button>
                <input type="number" id="itemQty" class="quantity-input" min="1" value="1">
                <button class="quantity-btn" onclick="changeItemQty(1)">+</button>
            </div>
            
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-primary" onclick="submitItemQty()">
                    Tambah ke Keranjang
                </button>
                <button class="popup-btn popup-btn-secondary" onclick="closeItemPopup()">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables for room
        let selectedRoomId = null;
        let roomName = '';
        let roomCapacity = 0;

        // Variables for item
        let selectedItemId = null;
        let maxStock = 0;
        let itemName = '';

        // ===== FUNGSI UNTUK RUANGAN =====
        function addRoomToCart(id, name, capacity) {
            selectedRoomId = id;
            roomName = name;
            roomCapacity = capacity;
            
            // Tampilkan popup konfirmasi
            document.getElementById("popupRoomName").textContent = name;
            document.getElementById("popupRoomCapacity").textContent = capacity;
            document.getElementById("roomConfirmationPopup").style.display = "flex";
        }

        function confirmAddRoom() {
            // Redirect untuk menambahkan ruangan ke keranjang (jumlah selalu 1)
            window.location.href = "cart_add.php?id=" + selectedRoomId + "&qty=1";
        }

        function closeRoomPopup(event) {
            if (event && event.target.id === 'roomConfirmationPopup') {
                document.getElementById("roomConfirmationPopup").style.display = "none";
            } else if (!event) {
                document.getElementById("roomConfirmationPopup").style.display = "none";
            }
        }

        // ===== FUNGSI UNTUK BARANG =====
        function openItemQtyPopup(id, stock, name) {
            selectedItemId = id;
            maxStock = stock;
            itemName = name;
            
            document.getElementById("popupItemName").textContent = name;
            document.getElementById("itemQty").value = 1;
            document.getElementById("itemQty").max = stock;
            document.getElementById("maxStock").textContent = stock;
            document.getElementById("itemQtyPopup").style.display = "flex";
        }

        function changeItemQty(amount) {
            const qtyInput = document.getElementById("itemQty");
            let currentQty = parseInt(qtyInput.value);
            let newQty = currentQty + amount;
            
            if (newQty < 1) newQty = 1;
            if (newQty > maxStock) newQty = maxStock;
            
            qtyInput.value = newQty;
        }

        function submitItemQty() {
            let qty = document.getElementById("itemQty").value;

            if (qty < 1) qty = 1;
            if (qty > maxStock) qty = maxStock;

            window.location.href = "cart_add.php?id=" + selectedItemId + "&qty=" + qty;
        }

        function closeItemPopup(event) {
            if (event && event.target.id === 'itemQtyPopup') {
                document.getElementById("itemQtyPopup").style.display = "none";
            } else if (!event) {
                document.getElementById("itemQtyPopup").style.display = "none";
            }
        }

        // ===== FUNGSI UMUM =====
        // Close popup with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRoomPopup();
                closeItemPopup();
            }
        });

        // Update input value when manually typed (for items)
        document.getElementById('itemQty').addEventListener('input', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) this.value = 1;
            if (value > maxStock) this.value = maxStock;
        });

        // Add animation to cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.item-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

</body>
</html>