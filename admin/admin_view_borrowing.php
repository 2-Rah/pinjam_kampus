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

$q = $conn->query("
    SELECT b.*, u.name AS user_name
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = $id
");

$borrow = $q->fetch_assoc();
if (!$borrow) {
    echo "Data tidak ditemukan.";
    exit;
}

// ambil detail
$detail = $conn->query("
SELECT d.quantity, i.name
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

/* ACTION BUTTONS */
.action-section {
    display: flex;
    justify-content: flex-start;
    gap: 16px;
    margin-top: 32px;
}

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

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
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
        <a href="logout_admin.php">Logout</a>
    </div>
</div>

<div class="container">
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
                <span class="info-label">Status</span>
                <span class="status-badge status-<?= $borrow['status'] ?>">
                    <?= $borrow['status'] ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Pengajuan</span>
                <span class="info-value"><?= date('d M Y H:i', strtotime($borrow['created_at'])) ?></span>
            </div>
        </div>
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
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $detail->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($d['name']) ?></strong>
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
    </div>
</div>

</body>
</html>