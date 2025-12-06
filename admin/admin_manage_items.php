<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

/**
 * Admin Manage Items
 * - Tambah, update, delete item
 * - Delete akan dicek dulu apakah ada dependency di borrowing_details
 */

// Handle tambah item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $category = trim($_POST['category']);
    $stock = $type === 'barang' ? (int)$_POST['stock'] : 1;
    $capacity = $type === 'ruangan' ? (int)$_POST['capacity'] : null;
    $description = trim($_POST['description']);

    // upload image
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'], "../gambar_item/" . $image_name);
    }

    $stmt = $conn->prepare("INSERT INTO items (name, type, category, stock, capacity, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $name, $type, $category, $stock, $capacity, $description, $image_name);
    if ($stmt->execute()) {
        $success_message = 'Item berhasil ditambahkan!';
    } else {
        $error_message = 'Gagal menambah item: ' . $stmt->error;
    }
    $stmt->close();
}

// Handle update item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 1;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // handle image update
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'], "../gambar_item/" . $image_name);
    }

    if ($image_name !== null) {
        // include image in update
        $stmt = $conn->prepare("UPDATE items SET name=?, category=?, stock=?, capacity=?, description=?, is_active=?, image=? WHERE id=?");
        $stmt->bind_param("ssiisisi", $name, $category, $stock, $capacity, $description, $is_active, $image_name, $id);
    } else {
        // without image
        $stmt = $conn->prepare("UPDATE items SET name=?, category=?, stock=?, capacity=?, description=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssiisii", $name, $category, $stock, $capacity, $description, $is_active, $id);
    }

    if ($stmt->execute()) {
        $success_message = 'Item berhasil diupdate!';
    } else {
        $error_message = 'Gagal update item: ' . $stmt->error;
    }
    $stmt->close();
}

// Handle delete item (dengan pengecekan FK)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // cek apakah item direferensi di borrowing_details
    $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM borrowing_details WHERE item_id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'] ?? 0;
    $chk->close();

    if ($cnt > 0) {
        // item masih dipakai, batalkan penghapusan
        $error_message = 'Tidak bisa menghapus item ini karena masih digunakan di peminjaman. Hapus dependensi peminjaman terlebih dahulu.';
    } else {
        // ambil nama file gambar (untuk dihapus dari server jika ada)
        $g = $conn->prepare("SELECT image FROM items WHERE id = ?");
        $g->bind_param("i", $id);
        $g->execute();
        $resg = $g->get_result()->fetch_assoc();
        $g->close();

        // hapus record
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // jika ada file gambar, hapus file fisik
            if (!empty($resg['image']) && file_exists("../gambar_item/" . $resg['image'])) {
                @unlink("../gambar_item/" . $resg['image']);
            }
            $success_message = 'Item berhasil dihapus!';
            header('Location: admin_manage_items.php');
            exit;
        } else {
            $error_message = 'Gagal menghapus item: ' . $stmt->error;
        }
    }
}

// Ambil semua items
$items = mysqli_query($conn, "SELECT * FROM items ORDER BY type, category, name");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Barang & Ruangan - Admin Dashboard</title>
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
    background: #f8fafc;
    color: #1e293b;
    line-height: 1.6;
}

/* NAVBAR SAMA PERSIS */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-brand {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.navbar-links {
    display: flex;
    gap: 24px;
    align-items: center;
}

.navbar-links a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-links a:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.navbar-links a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 16px;
    right: 16px;
    height: 2px;
    background: white;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.navbar-links a:hover::after {
    transform: scaleX(1);
}

/* ACTIVE LINK: Barang */
.navbar-links a[href="admin_manage_items.php"]::after,
.navbar-links a[href="admin_manage_items.php"]:hover::after {
    transform: scaleX(1);
}

.navbar-links a[href="admin_manage_items.php"] {
    background: rgba(255, 255, 255, 0.25);
}

/* CONTAINER */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px;
}

/* HEADER SECTION */
.header {
    background: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    animation: slideUp 0.6s ease-out;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.header p {
    color: #64748b;
    font-size: 16px;
}

/* MESSAGE STYLING */
.message {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: slideDown 0.5s ease-out;
}

.message.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: 1px solid #a7f3d0;
}

.message.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: 1px solid #fecaca;
}

.message svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* FORM SECTIONS */
.form-section {
    background: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    animation: slideUp 0.6s ease-out 0.1s both;
}

.form-section h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-section h3 svg {
    width: 20px;
    height: 20px;
    fill: #667eea;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #475569;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* BUTTONS */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* TABLE STYLING */
.table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 32px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475569;
    padding: 16px 20px;
    font-weight: 600;
    font-size: 14px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover {
    background: #f8fafc;
}

/* STATUS BADGES */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #d1fae5;
    color: #059669;
}

.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

/* TYPE BADGES */
.type-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.type-barang {
    background: #dbeafe;
    color: #2563eb;
}

.type-ruangan {
    background: #f3e8ff;
    color: #7c3aed;
}

/* IMAGE STYLING */
.item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* ACTION CELLS */
.action-cell {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* MODAL STYLING */
.modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    overflow-y: auto;
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: white;
    margin: 2rem auto;
    padding: 32px;
    width: 90%;
    max-width: 600px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-header h3 svg {
    width: 20px;
    height: 20px;
    fill: #667eea;
}

.close {
    font-size: 24px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: none;
    border: none;
}

.close:hover {
    color: #1e293b;
    background: #f1f5f9;
}

/* CHECKBOX STYLING */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    transform: scale(1.2);
}

/* ANIMATIONS */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 20px;
        flex-direction: column;
        gap: 12px;
    }

    .navbar-links {
        gap: 12px;
    }

    .container {
        padding: 20px;
    }

    .header,
    .form-section {
        padding: 24px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 800px;
    }

    .action-cell {
        flex-direction: column;
        gap: 8px;
    }

    .modal-content {
        margin: 1rem auto;
        padding: 24px;
        width: 95%;
    }
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-state svg {
    width: 64px;
    height: 64px;
    fill: #cbd5e1;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #475569;
}
</style>
</head>
<body>

<!-- NAVBAR SAMA PERSIS -->
<div class="navbar">
    <div class="navbar-brand">Admin Sistem Peminjaman</div>
    <div class="navbar-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_manage_role.php">User</a>
        <a href="admin_manage_items.php">Barang</a>
        <a href="admin_manage_borrowings.php">Peminjaman</a>
        <a href="admin_manage_returns.php">Pengembalian</a>
        <a href="logout_admin.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- MESSAGES -->
    <?php if (isset($success_message)): ?>
    <div class="message success">
        <svg viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        <span><?= htmlspecialchars($success_message) ?></span>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="message error">
        <svg viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        <span><?= htmlspecialchars($error_message) ?></span>
    </div>
    <?php endif; ?>

    <div class="header">
        <h1>Kelola Barang & Ruangan</h1>
        <p>Tambah, edit, atau hapus barang dan ruangan yang tersedia untuk peminjaman</p>
    </div>

    <!-- Form Tambah Item -->
    <div class="form-section">
        <h3>
            <svg viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Tambah Item Baru
        </h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Item *</label>
                    <input type="text" name="name" required placeholder="Masukkan nama item">
                </div>
                <div class="form-group">
                    <label>Tipe *</label>
                    <select name="type" id="type" required onchange="toggleFields()">
                        <option value="">Pilih Tipe</option>
                        <option value="barang">Barang</option>
                        <option value="ruangan">Ruangan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" required placeholder="e.g. Elektronik, Kelas, Lab">
                </div>
                <div class="form-group" id="stock-group">
                    <label>Stok *</label>
                    <input type="number" name="stock" value="1" min="1" placeholder="Jumlah stok">
                </div>
                <div class="form-group" id="capacity-group" style="display: none;">
                    <label>Kapasitas (orang)</label>
                    <input type="number" name="capacity" min="1" placeholder="Kapasitas ruangan">
                </div>
                <div class="form-group full">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Deskripsi lengkap item..."></textarea>
                </div>
                <div class="form-group full">
                    <label>Gambar Item</label>
                    <input type="file" name="image" accept="image/*">
                </div>
            </div>
            <button type="submit" name="add_item" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Tambah Item
            </button>
        </form>
    </div>

    <!-- Tabel Items -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Stok/Kapasitas</th>
                    <th>Status</th>
                    <th>Gambar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td>#<?= $item['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            <?php if ($item['description']): ?>
                                <br><small style="color: #64748b;"><?= htmlspecialchars($item['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="type-badge type-<?= $item['type'] ?>">
                                <?php if ($item['type'] === 'barang'): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"/>
                                    </svg>
                                    Barang
                                <?php else: ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 5v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5c-1.11 0-2 .9-2 2zm16 14H5V5h14v14z"/>
                                    </svg>
                                    Ruangan
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($item['category']) ?></td>
                        <td>
                            <?php if ($item['type'] === 'barang'): ?>
                                <strong><?= $item['stock'] ?></strong> unit
                            <?php else: ?>
                                <?= $item['capacity'] ? '<strong>' . $item['capacity'] . '</strong> orang' : '-' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $item['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td>
                            <?php if($item['image']): ?>
                                <img src="../gambar_item/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            <?php else: ?>
                                <div class="item-image" style="display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    <?= strtoupper(substr($item['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-cell">
                                <button onclick='editItem(<?= htmlspecialchars(json_encode($item)) ?>)' class="btn btn-warning btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                    Edit
                                </button>
                                <a href="?delete=<?= $item['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus item ini?')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                    </svg>
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 6h-4V4c0-1.1-.89-2-2-2h-4c-1.1 0-2 .89-2 2v2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6 0h-4V4h4v2z"/>
                                </svg>
                                <h3>Belum ada item</h3>
                                <p>Mulai dengan menambahkan item baru menggunakan form di atas.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <svg viewBox="0 0 24 24">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                </svg>
                Edit Item
            </h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" id="edit_type_hidden">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Item *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" id="edit_category" required>
                </div>
                <div class="form-group" id="edit_stock_group">
                    <label>Stok *</label>
                    <input type="number" name="stock" id="edit_stock" min="1">
                </div>
                <div class="form-group" id="edit_capacity_group" style="display: none;">
                    <label>Kapasitas (orang)</label>
                    <input type="number" name="capacity" id="edit_capacity" min="1">
                </div>
                <div class="form-group full">
                    <label>Deskripsi</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="form-group full">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label for="edit_is_active">Item Aktif</label>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Gambar Baru (opsional)</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color: #64748b; margin-top: 4px; display: block;">Biarkan kosong untuk mempertahankan gambar saat ini</small>
                </div>
            </div>
            <button type="submit" name="update_item" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Update Item
            </button>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('type').value;
    const stockGroup = document.getElementById('stock-group');
    const capacityGroup = document.getElementById('capacity-group');
    
    if (type === 'barang') {
        stockGroup.style.display = 'block';
        capacityGroup.style.display = 'none';
    } else if (type === 'ruangan') {
        stockGroup.style.display = 'none';
        capacityGroup.style.display = 'block';
    }
}

function editItem(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_category').value = item.category;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_is_active').checked = item.is_active == 1;
    document.getElementById('edit_type_hidden').value = item.type;
    
    if (item.type === 'barang') {
        document.getElementById('edit_stock_group').style.display = 'block';
        document.getElementById('edit_capacity_group').style.display = 'none';
        document.getElementById('edit_stock').value = item.stock;
    } else {
        document.getElementById('edit_stock_group').style.display = 'none';
        document.getElementById('edit_capacity_group').style.display = 'block';
        document.getElementById('edit_capacity').value = item.capacity || '';
    }
    
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Add smooth animations for table rows
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.style.animation = 'slideIn 0.5s ease-out forwards';
    });
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    tbody tr {
        opacity: 0;
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>