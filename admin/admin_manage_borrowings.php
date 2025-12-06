<?php
session_start();
require '../config.php';

// hanya admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['borrowing_id']);
    $status = $_POST['status'];
    $pickup_location = isset($_POST['pickup_location']) ? $conn->real_escape_string($_POST['pickup_location']) : null;

    $allowed = ['pending','approved','rejected','picked_up','completed','not_returned'];
    if (!in_array($status, $allowed)) {
        $status = 'pending';
    }

    // Update query dengan pickup_location
    if ($status == 'approved' && $pickup_location) {
        $stmt = $conn->prepare("UPDATE borrowings SET status=?, pickup_location=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $pickup_location, $id);
    } else {
        $stmt = $conn->prepare("UPDATE borrowings SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
    }
    
    $stmt->execute();

    header("Location: admin_manage_borrowings.php?msg=status_updated");
    exit;
}

// ambil data dengan informasi tambahan - DIKOREKSI: removed picked_up_at
$sql = "
SELECT b.id, b.user_id, b.status, b.created_at, b.description, b.title, b.judul, 
       b.pickup_location, b.start_date, b.end_date,
       u.name AS user_name,
       COUNT(d.id) as item_count
FROM borrowings b
JOIN users u ON b.user_id = u.id
LEFT JOIN borrowing_details d ON b.id = d.borrowing_id
GROUP BY b.id
ORDER BY b.created_at DESC
";
$borrowings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Peminjaman - Admin Dashboard</title>
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

/* ACTIVE LINK: Peminjaman */
.navbar-links a[href="admin_manage_borrowings.php"]::after,
.navbar-links a[href="admin_manage_borrowings.php"]:hover::after {
    transform: scaleX(1);
}

.navbar-links a[href="admin_manage_borrowings.php"] {
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.5px;
}

/* SUCCESS MESSAGE */
.alert-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    animation: slideDown 0.5s ease-out;
}

.alert-success svg {
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

/* FILTER SECTION */
.filter-section {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 200px;
}

.filter-group label {
    font-size: 14px;
    font-weight: 500;
    color: #475569;
}

.filter-select {
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* TABLE STYLING */
.table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
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
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
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

.status-not_returned {
    background: #fde68a;
    color: #92400e;
}

/* FORM ELEMENTS */
.form-group {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.select-wrapper {
    position: relative;
    min-width: 140px;
}

.select-wrapper select {
    width: 100%;
    padding: 8px 10px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    font-family: inherit;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
}

.select-wrapper::after {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-right: 2px solid #64748b;
    border-bottom: 2px solid #64748b;
    transform: translateY(-60%) rotate(45deg);
    pointer-events: none;
}

.select-wrapper select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* BUTTONS */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* ACTION CELLS */
.action-cell {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

/* INFO CHIPS */
.info-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 11px;
    color: #475569;
    margin-top: 4px;
    margin-right: 4px;
}

.info-chip svg {
    width: 10px;
    height: 10px;
    fill: #64748b;
}

.pickup-chip {
    background: #f0f9ff;
    color: #0369a1;
}

.date-chip {
    background: #fef3c7;
    color: #92400e;
}

/* BORROWING INFO */
.borrowing-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    display: block;
}

.borrowing-description {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* DATE INFO */
.date-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #64748b;
}

.date-item svg {
    width: 10px;
    height: 10px;
    fill: #64748b;
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

/* MODAL */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalIn 0.3s ease-out;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #64748b;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.modal-close:hover {
    background: #f1f5f9;
}

.modal-body {
    margin-bottom: 24px;
}

.modal-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 8px;
}

.modal-select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    margin-bottom: 16px;
}

.modal-input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    margin-bottom: 16px;
}

.modal-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
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

    .header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .filter-section {
        flex-direction: column;
    }

    .filter-group {
        min-width: 100%;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 1000px;
    }

    .action-cell {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-group {
        flex-direction: column;
        align-items: stretch;
    }
}

/* ANIMATIONS */
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
    animation: slideIn 0.5s ease-out forwards;
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
    <div class="header">
        <h1>Kelola Peminjaman Barang</h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert-success">
        <svg viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        <span>Status peminjaman berhasil diperbarui!</span>
    </div>
    <?php endif; ?>

    <!-- FILTER SECTION -->
    <div class="filter-section">
        <div class="filter-group">
            <label>Filter Status</label>
            <select class="filter-select" id="statusFilter">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="picked_up">Picked Up</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Urutkan Berdasarkan</label>
            <select class="filter-select" id="sortFilter">
                <option value="newest">Terbaru</option>
                <option value="oldest">Terlama</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul / Deskripsi</th>
                    <th>Peminjam</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Info</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($borrowings->num_rows > 0): ?>
                    <?php while ($row = $borrowings->fetch_assoc()): 
                        // Cek apakah sudah ada return yang approved
                        $return_check = $conn->query("
                            SELECT status FROM returns 
                            WHERE borrowing_id = {$row['id']} AND status = 'approved'
                        ")->fetch_assoc();
                        
                        // Update status jika perlu
                        if ($return_check && $row['status'] == 'picked_up') {
                            $conn->query("UPDATE borrowings SET status = 'completed' WHERE id = {$row['id']}");
                            $row['status'] = 'completed';
                        }
                        
                        // Format judul (gunakan judul atau title)
                        $judul = !empty($row['judul']) ? $row['judul'] : $row['title'];
                    ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td style="min-width: 250px;">
                            <span class="borrowing-title">
                                <?= htmlspecialchars($judul ?: 'Tidak ada judul') ?>
                            </span>
                            <?php if (!empty($row['description'])): ?>
                            <div class="borrowing-description">
                                <?= htmlspecialchars($row['description']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="date-info">
                                <div class="date-item">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                    </svg>
                                    Mulai: <?= date('d M Y', strtotime($row['start_date'])) ?>
                                </div>
                                <div class="date-item">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                    </svg>
                                    Selesai: <?= date('d M Y', strtotime($row['end_date'])) ?>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td>
                            <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $row['status'] ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <span class="info-chip">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                    </svg>
                                    <?= $row['item_count'] ?> barang
                                </span>
                                <?php if ($row['pickup_location']): ?>
                                <span class="info-chip pickup-chip">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                    </svg>
                                    <?= htmlspecialchars(mb_strimwidth($row['pickup_location'], 0, 20, '...')) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-cell">
                                <a href="admin_borrowing_detail.php?id=<?= $row['id'] ?>" class="btn btn-secondary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"/>
                                    </svg>
                                    Detail
                                </a>
                                <?php if ($row['status'] == 'picked_up'): ?>
                                <?php 
                                // Cek apakah sudah ada pengembalian
                                $return_exists = $conn->query("SELECT id FROM returns WHERE borrowing_id = {$row['id']}")->fetch_assoc();
                                ?>
                                <?php if ($return_exists): ?>
                                <a href="admin_return_detail.php?borrowing_id=<?= $row['id'] ?>" class="btn btn-success">
                                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: white;">
                                        <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.69 2.8l1.46 1.46C19.54 16.02 20 14.57 20 13c0-4.42-3.58-8-8-8zm-6.31 3.2L4.23 6.74C3.46 8 3 9.43 3 11c0 4.42 3.58 8 8 8v4l5-5-5-5v4c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.69-2.8z"/>
                                    </svg>
                                    Return
                                </a>
                                <?php else: ?>
                                <span class="btn btn-warning" style="cursor: default;">
                                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: white;">
                                        <path d="M11 15h2v2h-2zm0-8h2v6h-2zm.99-5C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                    </svg>
                                    Belum Return
                                </span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 11.5h.25V19h-4.5v-4.5H10c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1h.25V19h-4.5v-4.5H5c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1h.25V19H1V5h22v14h-3.75v-4.5H19c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1h-2c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1z"/>
                                </svg>
                                <h3>Tidak ada data peminjaman</h3>
                                <p>Belum ada permintaan peminjaman yang diajukan.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL UNTUK UPDATE STATUS -->
<div class="modal-overlay" id="statusModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ubah Status Peminjaman</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="statusForm">
            <input type="hidden" name="borrowing_id" id="modalBorrowingId">
            <div class="modal-body">
                <label class="modal-label">Status Baru</label>
                <select name="status" class="modal-select" id="modalStatus" onchange="togglePickupLocation()" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="picked_up">Picked Up</option>
                    <option value="completed">Completed</option>
                </select>
                
                <div id="pickupLocationSection" style="display: none;">
                    <label class="modal-label">Lokasi Pengambilan Barang</label>
                    <input type="text" 
                           name="pickup_location" 
                           class="modal-input" 
                           id="modalPickupLocation"
                           placeholder="Masukkan lokasi pengambilan barang">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Variables
let currentBorrowingId = null;
let currentPickupLocation = null;

// Open modal function
function openStatusModal(id, currentStatus, pickupLocation = '') {
    currentBorrowingId = id;
    currentPickupLocation = pickupLocation;
    
    document.getElementById('modalBorrowingId').value = id;
    document.getElementById('modalStatus').value = currentStatus;
    document.getElementById('modalPickupLocation').value = pickupLocation;
    
    // Show/hide pickup location based on selected status
    togglePickupLocation();
    
    // Show modal
    document.getElementById('statusModal').style.display = 'flex';
}

// Close modal function
function closeModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Toggle pickup location field
function togglePickupLocation() {
    const status = document.getElementById('modalStatus').value;
    const pickupSection = document.getElementById('pickupLocationSection');
    const pickupInput = document.getElementById('modalPickupLocation');
    
    if (status === 'approved') {
        pickupSection.style.display = 'block';
        pickupInput.required = true;
    } else {
        pickupSection.style.display = 'none';
        pickupInput.required = false;
    }
}

// Filter table by status
document.getElementById('statusFilter').addEventListener('change', function() {
    const filterValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
            const rowStatus = statusCell.textContent.toLowerCase().trim();
            if (!filterValue || rowStatus === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

// Sort table by date
document.getElementById('sortFilter').addEventListener('change', function() {
    const sortValue = this.value;
    const table = document.querySelector('tbody');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const dateA = new Date(a.querySelector('td:nth-child(4)').textContent.split('Diambil')[0].trim());
        const dateB = new Date(b.querySelector('td:nth-child(4)').textContent.split('Diambil')[0].trim());
        
        if (sortValue === 'newest') {
            return dateB - dateA;
        } else {
            return dateA - dateB;
        }
    });
    
    // Remove existing rows
    rows.forEach(row => row.remove());
    
    // Add sorted rows
    rows.forEach(row => table.appendChild(row));
});

// Add animation delays for rows
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Close modal when clicking outside
    document.getElementById('statusModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});

// Confirm before rejecting
document.getElementById('statusForm').addEventListener('submit', function(e) {
    const status = document.getElementById('modalStatus').value;
    
    if (status === 'rejected') {
        if (!confirm('Apakah Anda yakin ingin menolak peminjaman ini?')) {
            e.preventDefault();
        }
    }
    
    if (status === 'approved') {
        const pickupLocation = document.getElementById('modalPickupLocation').value;
        if (!pickupLocation.trim()) {
            alert('Harap isi lokasi pengambilan barang untuk status Approved');
            e.preventDefault();
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>