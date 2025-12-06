<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_manage_borrowings.php");
    exit;
}

$id = intval($_GET['id']);

// Query untuk mengambil data peminjaman dengan informasi user
$q = $conn->query("
    SELECT b.*, u.name AS user_name, u.nim_nip
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = $id
");

$borrow = $q->fetch_assoc();
if (!$borrow) {
    echo "Data tidak ditemukan.";
    exit;
}

// Cek apakah sudah ada return yang approved
$return_check = $conn->query("
    SELECT status FROM returns 
    WHERE borrowing_id = $id AND status = 'approved'
")->fetch_assoc();

// Update status menjadi 'completed' jika return sudah approved
if ($return_check && $borrow['status'] == 'picked_up') {
    $conn->query("UPDATE borrowings SET status = 'completed' WHERE id = $id");
    $borrow['status'] = 'completed';
}

// Proses update status jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $pickup_location = isset($_POST['pickup_location']) ? $conn->real_escape_string($_POST['pickup_location']) : null;
    $rejection_reason = isset($_POST['rejection_reason']) ? $conn->real_escape_string($_POST['rejection_reason']) : null;
    
    $update_query = "UPDATE borrowings SET status = '$new_status'";
    
    // Jika status diubah menjadi 'approved', update pickup_location
    if ($new_status == 'approved' && $pickup_location) {
        $update_query .= ", pickup_location = '$pickup_location'";
    }
    
    // Jika status diubah menjadi 'rejected', update rejection_reason
    if ($new_status == 'rejected' && $rejection_reason) {
        $update_query .= ", rejection_reason = '$rejection_reason'";
    } elseif ($new_status == 'rejected') {
        // Jika rejected tanpa alasan, set default message
        $update_query .= ", rejection_reason = 'Peminjaman ditolak oleh admin'";
    }
    
    $update_query .= " WHERE id = $id";
    
    if ($conn->query($update_query)) {
        // Jika status ditolak, kembalikan stok
        if ($new_status == 'rejected') {
            $conn->query("
                UPDATE items i
                JOIN borrowing_details d ON i.id = d.item_id
                SET i.stock = i.stock + d.quantity
                WHERE d.borrowing_id = $id
            ");
        }
        
        // Jika status disetujui, kurangi stok
        if ($new_status == 'approved') {
            $conn->query("
                UPDATE items i
                JOIN borrowing_details d ON i.id = d.item_id
                SET i.stock = i.stock - d.quantity
                WHERE d.borrowing_id = $id
            ");
        }
        
        header("Location: admin_borrowing_detail.php?id=$id&success=1");
        exit;
    } else {
        $error = "Gagal mengupdate status: " . $conn->error;
    }
}

// Ambil detail barang yang dipinjam dengan informasi lengkap
$detail = $conn->query("
    SELECT d.quantity, i.name, i.category, i.type, i.capacity, i.description as item_description
    FROM borrowing_details d
    JOIN items i ON d.item_id = i.id
    WHERE d.borrowing_id = $id
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Peminjaman - Admin Dashboard</title>
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

/* NAVBAR */
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

/* ACTIVE LINK: Peminjaman */
.navbar-links a[href="admin_manage_borrowings.php"]::after {
    transform: scaleX(1);
}

/* CONTAINER */
.container {
    max-width: 1000px;
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
    margin-bottom: 16px;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.header h1 svg {
    width: 24px;
    height: 24px;
    fill: currentColor;
}

/* SUCCESS MESSAGE */
.success-message {
    background: #d1fae5;
    color: #065f46;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #a7f3d0;
    animation: slideUp 0.3s ease-out;
}

.success-message svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

.error-message {
    background: #fee2e2;
    color: #991b1b;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #fecaca;
    animation: slideUp 0.3s ease-out;
}

/* INFO SECTION */
.info-section {
    background: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    animation: slideUp 0.6s ease-out 0.1s both;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-label {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

/* STATUS BADGE */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-approved {
    background: #d1fae5;
    color: #059669;
}

.status-rejected {
    background: #fee2e2;
    color: #dc2626;
}

.status-picked_up {
    background: #dbeafe;
    color: #2563eb;
}

.status-completed {
    background: #f3e8ff;
    color: #7c3aed;
}

/* REJECTION REASON BOX */
.rejection-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 16px;
    margin-top: 8px;
}

.rejection-label {
    font-size: 14px;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rejection-text {
    color: #991b1b;
    line-height: 1.5;
    font-size: 14px;
}

/* EDIT STATUS FORM */
.edit-status-form {
    background: #f8fafc;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-top: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 8px;
}

.form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    color: #1e293b;
    transition: border-color 0.3s ease;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    color: #1e293b;
    transition: border-color 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    color: #1e293b;
    min-height: 100px;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* TABLE SECTION */
.table-section {
    background: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    animation: slideUp 0.6s ease-out 0.2s both;
}

.table-section h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-section h3 svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

.table-container {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
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
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover {
    background: #f8fafc;
}

/* BUTTONS */
.btn {
    padding: 10px 20px;
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
}

.btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* ACTION BUTTONS */
.action-section {
    display: flex;
    justify-content: flex-start;
    gap: 16px;
    margin-top: 32px;
}

/* BORROWING DESCRIPTION */
.borrowing-description-box {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    margin-top: 16px;
    border-left: 4px solid #667eea;
}

.description-label {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.description-text {
    color: #1e293b;
    line-height: 1.6;
    font-size: 14px;
}

/* DATE INFO */
.date-info {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
}

.date-item svg {
    width: 14px;
    height: 14px;
    fill: #64748b;
}

/* ITEM INFO */
.item-info {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

.item-info span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-right: 12px;
}

/* ANIMATIONS */
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
    .info-section,
    .table-section {
        padding: 24px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .action-section {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        justify-content: center;
    }
    
    .date-info {
        flex-direction: column;
        gap: 8px;
    }
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 40px 20px;
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

/* PICKUP INFO */
.pickup-info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 16px;
    margin-top: 12px;
}

.pickup-info strong {
    color: #0369a1;
}
</style>
</head>
<body>

<!-- NAVBAR -->
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
    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        <svg viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        Status peminjaman berhasil diupdate!
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="error-message">
        <svg viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="header">
        <h1>
            <svg viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"/>
            </svg>
            Detail Peminjaman #<?= $borrow['id'] ?>
        </h1>
        <p>Informasi lengkap mengenai peminjaman ini</p>
    </div>

    <!-- INFO SECTION -->
    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">ID Peminjaman</span>
                <span class="info-value">#<?= $borrow['id'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Peminjam</span>
                <span class="info-value"><?= htmlspecialchars($borrow['user_name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">NIM/NIP</span>
                <span class="info-value"><?= htmlspecialchars($borrow['nim_nip'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="status-badge status-<?= $borrow['status'] ?>">
                    <?= $borrow['status'] ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Judul Peminjaman</span>
                <?php 
                $judul = !empty($borrow['judul']) ? $borrow['judul'] : $borrow['title'];
                ?>
                <span class="info-value"><?= htmlspecialchars($judul ?: 'Tidak ada judul') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Pengajuan</span>
                <span class="info-value"><?= date('d M Y H:i', strtotime($borrow['created_at'])) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Mulai</span>
                <span class="info-value"><?= date('d M Y', strtotime($borrow['start_date'])) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Selesai</span>
                <span class="info-value"><?= date('d M Y', strtotime($borrow['end_date'])) ?></span>
            </div>
            <?php if ($borrow['pickup_location']): ?>
            <div class="info-item">
                <span class="info-label">Lokasi Pengambilan</span>
                <span class="info-value"><?= htmlspecialchars($borrow['pickup_location']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tampilkan rejection reason jika ada -->
        <?php if (!empty($borrow['rejection_reason'])): ?>
        <div class="rejection-box">
            <div class="rejection-label">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: #dc2626;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                Alasan Penolakan
            </div>
            <div class="rejection-text">
                <?= nl2br(htmlspecialchars($borrow['rejection_reason'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Deskripsi Peminjaman -->
        <?php if (!empty($borrow['description'])): ?>
        <div class="borrowing-description-box">
            <div class="description-label">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: #667eea;">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                </svg>
                Deskripsi Peminjaman
            </div>
            <div class="description-text">
                <?= nl2br(htmlspecialchars($borrow['description'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- EDIT STATUS FORM -->
        <form method="POST" class="edit-status-form">
            <div style="margin-bottom: 16px; color: #475569; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #667eea;">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                </svg>
                Ubah Status Peminjaman
            </div>
            
            <div class="form-group">
                <label for="status">Status Saat Ini</label>
                <select name="status" id="status" class="form-select" required onchange="toggleAdditionalFields()">
                    <option value="pending" <?= $borrow['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $borrow['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $borrow['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="picked_up" <?= $borrow['status'] == 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                    <option value="completed" <?= $borrow['status'] == 'completed' ? 'selected' : '' ?> <?= $borrow['status'] == 'completed' ? 'disabled' : '' ?>>Completed</option>
                </select>
            </div>

            <!-- Pickup Location Field (muncul hanya untuk status approved) -->
            <div id="pickupLocationGroup" style="display: <?= $borrow['status'] == 'approved' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="pickup_location">Lokasi Pengambilan Barang</label>
                    <input type="text" 
                           name="pickup_location" 
                           id="pickup_location" 
                           class="form-input" 
                           placeholder="Masukkan lokasi pengambilan barang"
                           value="<?= htmlspecialchars($borrow['pickup_location'] ?? '') ?>"
                           <?= $borrow['status'] == 'approved' ? 'required' : '' ?>>
                </div>
            </div>

            <!-- Rejection Reason Field (muncul hanya untuk status rejected) -->
            <div id="rejectionReasonGroup" style="display: <?= $borrow['status'] == 'rejected' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="rejection_reason">Alasan Penolakan</label>
                    <textarea name="rejection_reason" 
                              id="rejection_reason" 
                              class="form-textarea" 
                              placeholder="Masukkan alasan penolakan peminjaman"
                              rows="4"><?= htmlspecialchars($borrow['rejection_reason'] ?? '') ?></textarea>
                    <small style="color: #64748b; font-size: 12px;">Alasan ini akan ditampilkan kepada peminjam</small>
                </div>
            </div>

            <button type="submit" name="update_status" class="btn btn-primary">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: white;">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Update Status
            </button>
        </form>
    </div>

    <!-- TABLE SECTION -->
    <div class="table-section">
        <h3>
            <svg viewBox="0 0 24 24">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7zm0 4h10v2H7zm0 4h7v2H7z"/>
            </svg>
            Barang yang Dipinjam
        </h3>
        
        <div class="table-container">
            <?php if ($detail->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Informasi</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $detail->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($d['name']) ?></strong>
                            <div class="item-info">
                                <span>
                                    <svg viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                    </svg>
                                    <?= htmlspecialchars($d['category']) ?>
                                </span>
                                <span>
                                    <svg viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <?= htmlspecialchars($d['type']) ?>
                                </span>
                                <?php if ($d['capacity']): ?>
                                <span>
                                    <svg viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                    </svg>
                                    <?= $d['capacity'] ?> orang
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($d['item_description'])): ?>
                            <div style="color: #64748b; font-size: 13px; line-height: 1.4;">
                                <?= htmlspecialchars($d['item_description']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-weight: 600;">
                                <?= $d['quantity'] ?> unit
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6h-4V4c0-1.1-.89-2-2-2h-4c-1.1 0-2 .89-2 2v2H4c-1.1 0-1.99.89-1.99 2L2 19c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.9-2-2-2zm-6 0h-4V4h4v2z"/>
                </svg>
                <h3>Tidak ada barang</h3>
                <p>Belum ada barang yang dipinjam dalam peminjaman ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="action-section">
        <a href="admin_manage_borrowings.php" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
            Kembali ke Daftar Peminjaman
        </a>
        
        <?php if ($borrow['status'] == 'picked_up'): ?>
        <?php 
        // Cek apakah sudah ada pengembalian
        $return_exists = $conn->query("SELECT id FROM returns WHERE borrowing_id = {$borrow['id']}")->fetch_assoc();
        ?>
        <?php if ($return_exists): ?>
        <a href="admin_return_detail.php?borrowing_id=<?= $borrow['id'] ?>" class="btn btn-success">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: white;">
                <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.69 2.8l1.46 1.46C19.54 16.02 20 14.57 20 13c0-4.42-3.58-8-8-8zm-6.31 3.2L4.23 6.74C3.46 8 3 9.43 3 11c0 4.42 3.58 8 8 8v4l5-5-5-5v4c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.69-2.8z"/>
            </svg>
            Lihat Pengembalian
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to toggle additional fields based on status selection
function toggleAdditionalFields() {
    const status = document.getElementById('status').value;
    const pickupLocationGroup = document.getElementById('pickupLocationGroup');
    const pickupLocationInput = document.getElementById('pickup_location');
    const rejectionReasonGroup = document.getElementById('rejectionReasonGroup');
    const rejectionReasonInput = document.getElementById('rejection_reason');
    
    // Reset all groups
    pickupLocationGroup.style.display = 'none';
    pickupLocationInput.required = false;
    rejectionReasonGroup.style.display = 'none';
    
    // Show appropriate group based on status
    if (status === 'approved') {
        pickupLocationGroup.style.display = 'block';
        pickupLocationInput.required = true;
    } else if (status === 'rejected') {
        rejectionReasonGroup.style.display = 'block';
    }
}

// Jika status adalah completed, disable form
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const form = document.querySelector('.edit-status-form');
    
    if (statusSelect.value === 'completed') {
        statusSelect.disabled = true;
        document.getElementById('pickup_location').disabled = true;
        document.getElementById('rejection_reason').disabled = true;
        document.querySelector('button[type="submit"]').disabled = true;
        document.querySelector('button[type="submit"]').style.opacity = '0.5';
        document.querySelector('button[type="submit"]').style.cursor = 'not-allowed';
    }
    
    // Initialize fields visibility
    toggleAdditionalFields();
});

// Confirm before rejecting
document.querySelector('form').addEventListener('submit', function(e) {
    const status = document.getElementById('status').value;
    
    if (status === 'rejected') {
        const rejectionReason = document.getElementById('rejection_reason').value;
        if (!rejectionReason.trim()) {
            alert('Harap isi alasan penolakan sebelum menolak peminjaman ini.');
            e.preventDefault();
            return;
        }
        
        if (!confirm('Apakah Anda yakin ingin menolak peminjaman ini? Stok barang akan dikembalikan dan alasan penolakan akan dikirim ke peminjam.')) {
            e.preventDefault();
        }
    }
    
    if (status === 'approved') {
        const pickupLocation = document.getElementById('pickup_location').value;
        if (!pickupLocation.trim()) {
            alert('Harap isi lokasi pengambilan barang untuk status Approved');
            e.preventDefault();
        }
    }
});
</script>

</body>
</html>