<?php
session_start();
require '../config.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ==============================
// HANDLE UPDATE STATUS
// ==============================
if (isset($_POST['update_status'])) {
    $return_id = intval($_POST['return_id']);
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? null;
    $admin_id = $_SESSION['admin_id'];

    // 1. Update return status
    $stmt = $conn->prepare("
        UPDATE returns 
        SET status = ?, rejection_reason = ?, approved_by = ?, approved_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $status, $reason, $admin_id, $return_id);
    $stmt->execute();

    // 2. Jika status disetujui (approved), update status borrowing ke completed DAN update stock barang
    if ($status === 'approved') {
        // Dapatkan borrowing_id dari return
        $get_borrow_id = mysqli_query($conn, "
            SELECT borrowing_id FROM returns WHERE id = $return_id
        ");
        $borrow_data = mysqli_fetch_assoc($get_borrow_id);
        $borrowing_id = $borrow_data['borrowing_id'];
        
        // Update status borrowing ke 'completed'
        mysqli_query($conn, "
            UPDATE borrowings 
            SET status = 'completed' 
            WHERE id = $borrowing_id
        ");

        // 3. AMBIL SEMUA ITEM YANG DIKEMBALIKAN DAN UPDATE STOCK
        $return_items = mysqli_query($conn, "
            SELECT 
                rd.item_id,
                rd.quantity AS returned_qty,
                rd.item_condition,
                i.name AS item_name,
                i.stock AS current_stock
            FROM return_details rd
            JOIN items i ON i.id = rd.item_id
            WHERE rd.return_id = $return_id
        ");

        while ($item = mysqli_fetch_assoc($return_items)) {
            $item_id = $item['item_id'];
            $returned_qty = $item['returned_qty'];
            $item_condition = $item['item_condition'];
            $current_stock = $item['current_stock'];
            $item_name = $item['item_name'];

            // Hanya update stock jika kondisi barang BAIK (good)
            if ($item_condition === 'good') {
                $new_stock = $current_stock + $returned_qty;
                
                mysqli_query($conn, "
                    UPDATE items 
                    SET stock = $new_stock 
                    WHERE id = $item_id
                ");

                // Log stock update (optional)
                mysqli_query($conn, "
                    INSERT INTO stock_logs (item_id, change_type, quantity, note, created_by)
                    VALUES ($item_id, 'return', $returned_qty, 'Pengembalian barang #$return_id', $admin_id)
                ");
            } else {
                // Jika barang rusak/hilang, tidak update stock (tetap dianggap habis)
                // Atau bisa juga mengurangi stock tambahan untuk penggantian
                mysqli_query($conn, "
                    INSERT INTO stock_logs (item_id, change_type, quantity, note, created_by)
                    VALUES ($item_id, 'damaged_return', 0, 'Barang $item_condition - tidak dikembalikan ke stock', $admin_id)
                ");
            }
        }
    }

    header("Location: admin_manage_returns.php?view=$return_id&updated=1");
    exit;
}

// ==============================
// AMBIL SEMUA RETURNS
// ==============================
$list = mysqli_query($conn, "
    SELECT 
        r.id, 
        r.borrowing_id, 
        b.title AS borrow_title,
        b.description AS borrow_desc,
        r.user_id, 
        r.status, 
        r.created_at, 
        u.name AS username,
        b.status AS borrowing_status
    FROM returns r
    JOIN users u ON u.id = r.user_id
    JOIN borrowings b ON b.id = r.borrowing_id
    ORDER BY r.id DESC
");

// ==============================
// DETAIL RETURN
// ==============================
$detail = null;
$items = [];

if (isset($_GET['view'])) {
    $return_id = intval($_GET['view']);

    $d = mysqli_query($conn, "
        SELECT 
            r.*, 
            u.name AS username,
            b.title AS borrow_title,
            b.description AS borrow_desc,
            b.status AS borrowing_status
        FROM returns r
        JOIN users u ON u.id = r.user_id
        JOIN borrowings b ON b.id = r.borrowing_id
        WHERE r.id = $return_id
    ");

    $detail = mysqli_fetch_assoc($d);

    // Detail barang dengan stock info
    $items = mysqli_query($conn, "
        SELECT 
            rd.*, 
            i.name AS item_name, 
            i.type AS item_type,
            i.category AS item_category,
            i.stock AS current_stock,
            bd.quantity AS borrowed_qty
        FROM return_details rd
        JOIN items i ON i.id = rd.item_id
        JOIN borrowing_details bd ON bd.id = rd.borrowing_detail_id
        WHERE rd.return_id = $return_id
    ");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengembalian • Admin Dashboard</title>
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
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 32px;
            gap: 24px;
            height: calc(100vh - 80px - 64px);
        }

        /* Sidebar List */
        .sidebar {
            flex: 0 0 350px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sidebar-header h2 {
            font-weight: 700;
            font-size: 20px;
            color: #1e293b;
        }

        .return-list {
            padding: 16px;
        }

        .return-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            text-decoration: none;
            display: block;
            color: inherit;
            transition: all 0.25s ease;
        }

        .return-card:hover {
            border-color: #c7d2fe;
            background: #f8fafc;
            transform: translateY(-2px);
        }

        .return-card.active {
            border: 2px solid #6366f1;
            background: #f0f4ff;
        }

        .return-id {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }

        .return-title {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .return-user {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .return-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #94a3b8;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            margin-top: 8px;
        }

        .status-pending    { background: #fef9c3; color: #92400e; }
        .status-approved   { background: #dcfce7; color: #166534; }
        .status-rejected   { background: #fee2e2; color: #b91c1c; }
        .status-completed  { background: #dcfce7; color: #166534; }

        /* Status untuk borrowing */
        .borrowing-status {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #475569;
            margin-left: 8px;
        }

        .borrowing-status.completed { background: #dcfce7; color: #166534; }

        /* Main Content */
        .main-content {
            flex: 1;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .content-header {
            padding: 24px 32px;
            border-bottom: 1px solid #e2e8f0;
        }

        .content-header h1 {
            font-weight: 700;
            font-size: 24px;
            color: #1e293b;
        }

        .content-body {
            padding: 32px;
            flex: 1;
        }

        .placeholder {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .placeholder p {
            font-size: 18px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .summary-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .summary-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .summary-status .summary-value {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .borrow-status-info {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        th {
            background: #f1f5f9;
            text-align: left;
            padding: 14px 16px;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
        }

        .item-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .item-type-badge {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .item-category-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .stock-info {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stock-change {
            font-size: 12px;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .stock-increase {
            background: #dcfce7;
            color: #166534;
        }

        .stock-no-change {
            background: #f3f4f6;
            color: #4b5563;
        }

        .condition-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .condition-good { 
            background: #dcfce7; 
            color: #166534; 
            border: 1px solid #bbf7d0;
        }
        .condition-damaged { 
            background: #fee2e2; 
            color: #b91c1c; 
            border: 1px solid #fecaca;
        }
        .condition-lost { 
            background: #f3f4f6; 
            color: #4b5563; 
            border: 1px solid #e5e7eb;
        }
        .condition-needs_repair { 
            background: #fef9c3; 
            color: #92400e; 
            border: 1px solid #fde68a;
        }

        .view-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .view-btn:hover {
            opacity: 0.9;
            transform: scale(1.03);
        }

        .form-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
            border: 1px solid #e2e8f0;
        }

        .form-section h3 {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #334155;
        }

        select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            background: white;
        }

        select:focus, textarea:focus {
            outline: none;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Reason Field Container */
        .reason-container {
            display: none;
            margin-top: 8px;
            animation: fadeIn 0.3s ease;
        }

        .reason-container.show {
            display: block;
        }

        /* Alert Warning */
        .alert-warning {
            padding: 12px 20px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Alert Success */
        .alert-success {
            padding: 12px 20px;
            background: #dcfce7;
            color: #166534;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stock Impact Notice */
        .stock-impact-notice {
            padding: 12px 16px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 8px;
            margin: 16px 0;
            border-left: 4px solid #0ea5e9;
        }

        .stock-impact-notice h4 {
            font-weight: 600;
            margin-bottom: 4px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.9); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .modal img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            display: block;
        }

        .close-btn {
            position: absolute;
            top: -40px;
            right: 0;
            font-size: 36px;
            color: white;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .close-btn:hover {
            background: rgba(0,0,0,0.8);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                height: auto;
                padding: 20px 16px;
            }
            .sidebar {
                flex: auto;
                max-height: 300px;
            }
        }

        @media (max-width: 600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .content-body {
                padding: 20px;
            }
            .navbar {
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
            }
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
        <a href="admin_manage_returns.php" style="position: relative;">
            <span>Pengembalian</span>
            <span style="position:absolute; bottom:-2px; left:0; right:0; height:2px; background:white; transform:scaleX(1);"></span>
        </a>
        <a href="logout_admin.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- Sidebar List -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Daftar Pengembalian</h2>
        </div>
        <div class="return-list">
            <?php 
            mysqli_data_seek($list, 0);
            while ($r = mysqli_fetch_assoc($list)): 
            $isActive = (isset($_GET['view']) && $_GET['view'] == $r['id']);
            ?>
                <a href="admin_manage_returns.php?view=<?= $r['id'] ?>" class="return-card <?= $isActive ? 'active' : '' ?>">
                    <div class="return-id">Return #<?= $r['id'] ?> • Borrow #<?= $r['borrowing_id'] ?></div>
                    <div class="return-title">
                        <?= htmlspecialchars($r['borrow_title'] ?: $r['borrow_desc']) ?>
                        <?php if ($r['borrowing_status'] == 'completed'): ?>
                            <span class="borrowing-status completed">completed</span>
                        <?php endif; ?>
                    </div>
                    <div class="return-user">oleh <?= htmlspecialchars($r['username']) ?></div>
                    <div class="return-meta">
                        <span><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                        <span class="status-badge status-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h1><?= $detail ? 'Detail Pengembalian #' . $detail['id'] : 'Kelola Pengembalian' ?></h1>
        </div>

        <div class="content-body">
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Status berhasil diperbarui.
                </div>
            <?php endif; ?>

            <?php if (!$detail): ?>
                <div class="placeholder">
                    <p>👈 Silakan pilih pengembalian dari daftar di sebelah kiri.</p>
                </div>
            <?php else: ?>
                <!-- Borrowing Status Warning -->
                <?php if ($detail['borrowing_status'] == 'completed'): ?>
                    <div class="alert-warning">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Status peminjaman sudah <strong>completed</strong>. Tidak dapat mengubah status pengembalian.
                    </div>
                <?php endif; ?>

                <!-- Stock Impact Notice -->
                <?php if ($detail['status'] == 'pending'): ?>
                    <div class="stock-impact-notice">
                        <h4>Perhatikan Dampak Stock!</h4>
                        <p>Jika pengembalian disetujui:</p>
                        <ul style="margin: 8px 0 8px 20px; color: #0c4a6e;">
                            <li>Barang dengan kondisi <strong>BAIK</strong> akan dikembalikan ke stock</li>
                            <li>Barang <strong>rusak/hilang</strong> tidak akan ditambahkan ke stock</li>
                            <li>Status peminjaman akan diubah menjadi <strong>completed</strong></li>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Judul Peminjaman</div>
                        <div class="summary-value"><?= htmlspecialchars($detail['borrow_title'] ?: $detail['borrow_desc']) ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Pengguna</div>
                        <div class="summary-value"><?= htmlspecialchars($detail['username']) ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Tanggal Ajukan</div>
                        <div class="summary-value"><?= date('d M Y H:i', strtotime($detail['created_at'])) ?></div>
                    </div>
                    <div class="summary-card summary-status">
                        <div class="summary-label">Status Pengembalian</div>
                        <div class="summary-value">
                            <span class="status-badge status-<?= $detail['status'] ?>">
                                <?= ucfirst($detail['status']) ?>
                            </span>
                            <?php if ($detail['borrowing_status'] == 'completed'): ?>
                                <span class="borrowing-status completed">completed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($detail['rejection_reason'])): ?>
                    <div class="summary-card" style="background:#fef2f2; border-color:#fecaca;">
                        <div class="summary-label" style="color:#b91c1c;">Alasan Penolakan</div>
                        <div class="summary-value" style="color:#b91c1c;"><?= htmlspecialchars($detail['rejection_reason']) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Items Table -->
                <h3 style="font-weight:600; font-size:18px; margin:24px 0 12px;">Barang yang Dikembalikan</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Dipinjam</th>
                            <th>Dikembalikan</th>
                            <th>Kondisi</th>
                            <th>Dampak Stock</th>
                            <th>Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($items, 0);
                        while ($i = mysqli_fetch_assoc($items)): 
                            // Tentukan dampak pada stock
                            $stock_impact = '';
                            $stock_class = '';
                            if ($i['item_condition'] === 'good') {
                                $stock_impact = "+{$i['quantity']}";
                                $stock_class = 'stock-increase';
                                $stock_message = "Stock akan ditambah {$i['quantity']}";
                            } else {
                                $stock_impact = "0";
                                $stock_class = 'stock-no-change';
                                $stock_message = ucwords($i['item_condition']) . " - tidak ditambahkan ke stock";
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="item-info">
                                    <div class="item-name"><?= htmlspecialchars($i['item_name']) ?></div>
                                    <div class="item-meta">
                                        <span class="item-type-badge"><?= htmlspecialchars($i['item_type']) ?></span>
                                        <?php if (!empty($i['item_category'])): ?>
                                            <span class="item-category-badge"><?= htmlspecialchars($i['item_category']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stock-info">
                                        <span>Stock saat ini: <?= $i['current_stock'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><b><?= $i['borrowed_qty'] ?></b></td>
                            <td><?= $i['quantity'] ?></td>
                            <td>
                                <span class="condition-badge condition-<?= $i['item_condition'] ?>" title="<?= ucwords(str_replace('_', ' ', $i['item_condition'])) ?>">
                                    <?= ucwords(str_replace('_', ' ', $i['item_condition'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="stock-change <?= $stock_class ?>" title="<?= $stock_message ?>">
                                    <?= $stock_impact ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($i['image'])): ?>
                                    <button class="view-btn" 
                                        onclick="openModal('../user/<?= htmlspecialchars($i['image']) ?>')">
                                        Lihat
                                    </button>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic;">–</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Update Form -->
                <?php if ($detail['borrowing_status'] != 'completed'): ?>
                    <div class="form-section">
                        <h3>Perbarui Status Pengembalian</h3>
                        <form method="POST" id="updateForm" onsubmit="return validateForm()">
                            <input type="hidden" name="return_id" value="<?= $detail['id'] ?>">

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" required onchange="toggleReasonField()">
                                    <option value="pending"    <?= $detail['status']=='pending'   ?'selected':'' ?>>Pending</option>
                                    <option value="approved"   <?= $detail['status']=='approved'  ?'selected':'' ?>>Disetujui</option>
                                    <option value="rejected"   <?= $detail['status']=='rejected'  ?'selected':'' ?>>Ditolak</option>
                                </select>
                                <small style="color:#64748b; display:block; margin-top:4px;">
                                    Jika disetujui:
                                    <ul style="margin: 4px 0 0 20px; color: #475569;">
                                        <li>Status peminjaman akan diubah menjadi "completed"</li>
                                        <li>Barang dengan kondisi BAIK akan dikembalikan ke stock</li>
                                        <li>Barang rusak/hilang tidak akan ditambahkan ke stock</li>
                                    </ul>
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="reason-container" id="reasonContainer">
                                    <label for="reason">Alasan Penolakan (Opsional)</label>
                                    <textarea name="reason" id="reason" rows="3" placeholder="Tulis alasan penolakan..."><?= htmlspecialchars($detail['rejection_reason'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <button type="submit" name="update_status" class="btn-submit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="form-section" style="background:#f8fafc; border-color:#d1fae5;">
                        <h3 style="color:#166534;">Pengembalian Telah Selesai</h3>
                        <p style="color:#475569; margin-bottom:0;">
                            Peminjaman ini sudah berstatus <strong>completed</strong>. 
                            Status pengembalian tidak dapat diubah lagi.
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="imgModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <img id="modalImage" alt="Foto kondisi barang">
    </div>
</div>

<script>
    // Function to toggle reason field based on status selection
    function toggleReasonField() {
        const status = document.getElementById('status').value;
        const reasonContainer = document.getElementById('reasonContainer');
        
        if (status === 'rejected') {
            reasonContainer.classList.add('show');
        } else {
            reasonContainer.classList.remove('show');
        }
    }

    // Form validation
    function validateForm() {
        const status = document.getElementById('status').value;
        
        if (status === 'rejected') {
            const reason = document.getElementById('reason').value;
            if (reason.trim() === '') {
                if (!confirm('Status pengembalian akan ditetapkan sebagai "Ditolak" tanpa alasan. Lanjutkan?')) {
                    return false;
                }
            }
        }
        
        if (status === 'approved') {
            let message = 'Apakah Anda yakin ingin menyetujui pengembalian ini?\n\n';
            message += 'Dampak yang akan terjadi:\n';
            message += '1. Status peminjaman akan diubah menjadi "completed"\n';
            message += '2. Barang dengan kondisi BAIK akan dikembalikan ke stock\n';
            message += '3. Barang rusak/hilang TIDAK akan ditambahkan ke stock\n\n';
            message += 'Lanjutkan?';
            
            if (!confirm(message)) {
                return false;
            }
        }
        
        return true;
    }

    // Initialize reason field visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleReasonField();
    });

    // Image modal functions
    function openModal(src) {
        const modal = document.getElementById("imgModal");
        const img = document.getElementById("modalImage");
        img.src = src;
        modal.classList.add("show");
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        const modal = document.getElementById("imgModal");
        modal.classList.remove("show");
        document.body.style.overflow = "";
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape") closeModal();
    });

    // Close on click outside image
    document.getElementById("imgModal").addEventListener('click', (e) => {
        if (e.target === document.getElementById("imgModal")) closeModal();
    });
</script>

</body>
</html>