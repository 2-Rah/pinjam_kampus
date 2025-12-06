<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* ===========================================================
   Ambil borrowing + details + LATEST status return + TITLE
   =========================================================== */
$sql = "
SELECT 
    b.id AS borrowing_id,
    b.title AS borrowing_title,
    b.description,
    b.start_date,
    b.end_date,
    b.created_at AS borrow_date,
    b.status AS borrow_status,
    b.pickup_location,
    
    (SELECT status FROM returns WHERE borrowing_id = b.id ORDER BY created_at DESC LIMIT 1) AS latest_return_status,
    (SELECT rejection_reason FROM returns WHERE borrowing_id = b.id ORDER BY created_at DESC LIMIT 1) AS return_rejection_reason,
    (SELECT created_at FROM returns WHERE borrowing_id = b.id ORDER BY created_at DESC LIMIT 1) AS return_request_date,
    (SELECT id FROM returns WHERE borrowing_id = b.id ORDER BY created_at DESC LIMIT 1) AS return_id,
    
    i.name AS item_name,
    i.type AS item_type,
    i.category AS item_category,
    i.image AS item_image,
    bd.quantity

FROM borrowings b
JOIN borrowing_details bd ON bd.borrowing_id = b.id
JOIN items i ON i.id = bd.item_id

WHERE b.user_id = $user_id
  AND b.status IN ('approved','picked_up','borrowed')

ORDER BY b.id DESC, bd.id
";

$query = mysqli_query($conn, $sql);

/* ===========================================================
   Kelompokkan per borrowing
   =========================================================== */
$borrowings = [];

while ($row = mysqli_fetch_assoc($query)) {
    $id = $row['borrowing_id'];

    if (!isset($borrowings[$id])) {
        // Tentukan judul peminjaman
        $borrowing_title = $row['borrowing_title'];
        if (empty($borrowing_title)) {
            $borrowing_title = "Peminjaman #" . $id;
        }
        
        // Tentukan status gabungan berdasarkan latest return status
        $final_status = "Belum Dikembalikan";
        $show_button = true;
        $button_text = "Ajukan Pengembalian";
        $button_type = "submit";
        $button_class = "btn-primary";

        if ($row['latest_return_status']) {
            $final_status = $row['latest_return_status'];
            
            if ($row['latest_return_status'] == 'pending') {
                $show_button = true;
                $button_text = "Periksa Pengajuan";
                $button_type = "button";
                $button_class = "btn-secondary";
            } elseif ($row['latest_return_status'] == 'rejected') {
                $show_button = true;
                $button_text = "Edit Pengajuan";
                $button_type = "submit";
                $button_class = "btn-warning";
            } elseif ($row['latest_return_status'] == 'completed') {
                $show_button = false;
                $final_status = "Selesai"; // Tampilkan sebagai "Selesai"
            } elseif ($row['latest_return_status'] == 'approved') {
                // PERUBAHAN: Jika return_status 'approved', maka statusnya "Selesai"
                $final_status = "Selesai";
                $show_button = false;
            }
        } else {
            if ($row['borrow_status'] == "approved") {
                $final_status = "Menunggu Diambil";
                $show_button = false;
            }
        }

        $borrowings[$id] = [
            "title" => $borrowing_title,
            "description" => $row['description'],
            "start_date" => $row['start_date'],
            "end_date" => $row['end_date'],
            "borrow_date" => $row['borrow_date'],
            "pickup_location" => $row['pickup_location'],
            "status" => $final_status,
            "return_id" => $row['return_id'],
            "latest_return_status" => $row['latest_return_status'],
            "return_rejection_reason" => $row['return_rejection_reason'],
            "return_request_date" => $row['return_request_date'],
            "show_button" => $show_button,
            "button_text" => $button_text,
            "button_type" => $button_type,
            "button_class" => $button_class,
            "items" => []
        ];
    }

    // Tambahkan item jika belum ada dalam array
    $item_exists = false;
    foreach ($borrowings[$id]["items"] as $existing_item) {
        if ($existing_item["name"] == $row["item_name"] && $existing_item["qty"] == $row["quantity"]) {
            $item_exists = true;
            break;
        }
    }
    
    if (!$item_exists) {
        $borrowings[$id]["items"][] = [
            "name" => $row["item_name"],
            "qty" => $row["quantity"],
            "type" => $row["item_type"],
            "category" => $row["item_category"],
            "image" => $row["item_image"]
        ];
    }
}

// Status color mapping - TAMBAH 'Selesai'
$status_colors = [
    'pending' => '#f59e0b',
    'approved' => '#10b981',
    'rejected' => '#ef4444',
    'completed' => '#8b5cf6',
    'Selesai' => '#10b981', // Warna hijau untuk Selesai
    'Belum Dikembalikan' => '#3b82f6',
    'Menunggu Diambil' => '#8b5cf6',
    'Disetujui - Menunggu Konfirmasi' => '#10b981'
];

$status_text = [
    'pending' => 'Menunggu Persetujuan',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'completed' => 'Selesai',
    'Selesai' => 'Selesai', // Teks untuk Selesai
    'Belum Dikembalikan' => 'Belum Dikembalikan',
    'Menunggu Diambil' => 'Menunggu Diambil',
    'Disetujui - Menunggu Konfirmasi' => 'Disetujui - Menunggu Konfirmasi'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang • Sistem Peminjaman</title>
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

        /* ACTIVE LINK */
        .navbar-links a[href="user_return_selection.php"]::after,
        .navbar-links a[href="user_return_selection.php"]:hover::after {
            transform: scaleX(1);
        }

        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 32px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.3;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        .empty-state svg {
            width: 200px;
            height: 200px;
            stroke: #cbd5e0;
            stroke-width: 1;
            margin-bottom: 24px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #475569;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 16px;
        }

        /* Borrowing Cards */
        .borrowing-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .borrowing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .card-header {
            padding: 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
        }

        .borrowing-info {
            flex: 1;
            min-width: 300px;
        }

        .borrowing-title-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .borrowing-main-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        .borrowing-subtitle {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .borrowing-id {
            background: #e2e8f0;
            color: #475569;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .borrowing-dates {
            display: flex;
            gap: 20px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #64748b;
        }

        .date-icon {
            color: #667eea;
            flex-shrink: 0;
        }

        .status-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: flex-end;
            min-width: 200px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            width: fit-content;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
        }

        /* Card Content */
        .card-content {
            padding: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Description Box */
        .description-box {
            background: #f8fafc;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }

        .description-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .description-content {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            white-space: pre-line;
        }

        /* Rejection Box */
        .rejection-box {
            background: #fef2f2;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #ef4444;
            margin-bottom: 20px;
        }

        .rejection-title {
            font-size: 14px;
            font-weight: 600;
            color: #b91c1c;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rejection-content {
            font-size: 14px;
            color: #b91c1c;
            line-height: 1.6;
            white-space: pre-line;
        }

        /* Pickup Location Box */
        .pickup-box {
            background: #eff6ff;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 20px;
        }

        .pickup-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pickup-content {
            font-size: 14px;
            color: #1e40af;
            line-height: 1.6;
        }

        /* Items Grid */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .item-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .item-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }

        .item-type {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .item-category {
            background: #f1f5f9;
            color: #475569;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .item-quantity {
            font-size: 13px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Return Request Info */
        .return-info-box {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
            margin-bottom: 20px;
        }

        .return-info-title {
            font-size: 14px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .return-info-content {
            font-size: 14px;
            color: #166534;
            line-height: 1.6;
        }

        /* Completed Status Box */
        .completed-box {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
            margin-bottom: 20px;
        }

        .completed-title {
            font-size: 14px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .completed-content {
            font-size: 14px;
            color: #166534;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 20px;
                flex-direction: column;
                gap: 12px;
            }

            .navbar-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .container {
                padding: 0 15px;
            }

            .page-header {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .borrowing-info {
                min-width: 100%;
            }

            .status-section {
                align-items: stretch;
                width: 100%;
            }

            .action-buttons {
                width: 100%;
                flex-wrap: wrap;
            }

            .btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .borrowing-dates {
                flex-direction: column;
                gap: 8px;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-brand">Sistem Peminjaman - User</div>
        <div class="navbar-links">
            <a href="user_dashboard.php">Dashboard</a>
            <a href="barang_list.php">Daftar Barang</a>
            <a href="my_borrowings.php">Peminjaman Saya</a>
            <a href="user_return_selection.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 17l-5 5v-18a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                    <path d="M9 8h6"></path>
                    <path d="M9 12h6"></path>
                    <path d="M9 16h4"></path>
                    <path d="M19 16l-2 3h4l-2 3"></path>
                </svg>
                Pengembalian Barang
            </h1>
            <p class="page-subtitle">Pilih peminjaman untuk diajukan pengembalian</p>
        </div>

        <?php if (empty($borrowings)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M9 17l-5 5v-18a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                    <path d="M9 8h6"></path>
                    <path d="M9 12h6"></path>
                    <path d="M9 16h4"></path>
                    <path d="M19 16l-2 3h4l-2 3"></path>
                </svg>
                <h3>Tidak Ada Peminjaman Aktif</h3>
                <p>Tidak ada peminjaman yang memerlukan pengembalian saat ini</p>
            </div>

        <?php else: ?>
            <!-- Borrowing List -->
            <?php foreach ($borrowings as $id => $b): 
                $status_color = $status_colors[$b['status']] ?? '#6b7280';
                $status_text_display = $status_text[$b['status']] ?? $b['status'];
            ?>
            <div class="borrowing-card">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="borrowing-info">
                        <div class="borrowing-title-container">
                            <!-- JUDUL UTAMA PEMINJAMAN -->
                            <div class="borrowing-main-title"><?= htmlspecialchars($b['title']) ?></div>
                            
                            <div class="borrowing-subtitle">
                                <span class="borrowing-id">#<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></span>
                            </div>
                        </div>
                        
                        <div class="borrowing-dates">
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="date-icon">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Tanggal Pinjam: <?= date('d M Y', strtotime($b['borrow_date'])) ?>
                            </div>
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="date-icon">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Mulai: <?= date('d M Y', strtotime($b['start_date'])) ?>
                            </div>
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="date-icon">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Selesai: <?= date('d M Y', strtotime($b['end_date'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="status-section">
                        <div class="status-badge" style="background: <?= $status_color ?>20; color: <?= $status_color ?>;">
                            <span class="status-indicator" style="background: <?= $status_color ?>;"></span>
                            <?= $status_text_display ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($b['show_button']): ?>
                                <?php if ($b['button_type'] == 'submit'): ?>
                                    <form action="user_return_borrowing.php" method="GET" style="display:inline;">
                                        <input type="hidden" name="borrow_id" value="<?= $id ?>">
                                        <?php if ($b['latest_return_status'] == 'rejected' && $b['return_id']): ?>
                                            <input type="hidden" name="edit_return" value="<?= $b['return_id'] ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="btn <?= $b['button_class'] ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <?php if ($b['latest_return_status'] == 'rejected'): ?>
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                <?php else: ?>
                                                    <path d="M9 17l-5 5v-18a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                                                    <path d="M9 8h6"></path>
                                                    <path d="M9 12h6"></path>
                                                <?php endif; ?>
                                            </svg>
                                            <?= $b['button_text'] ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn <?= $b['button_class'] ?>" onclick="alert('Pengajuan pengembalian sedang dalam proses persetujuan. Silakan tunggu konfirmasi dari admin.')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <?= $b['button_text'] ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="card-content">
                    <?php if (!empty($b['description'])): ?>
                    <div class="description-box">
                        <div class="description-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Deskripsi Peminjaman
                        </div>
                        <div class="description-content"><?= htmlspecialchars($b['description']) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Pickup Location -->
                    <?php if (!empty($b['pickup_location'])): ?>
                    <div class="pickup-box">
                        <div class="pickup-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Lokasi Pengambilan
                        </div>
                        <div class="pickup-content"><?= htmlspecialchars($b['pickup_location']) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Return Rejection Reason -->
                    <?php if ($b['latest_return_status'] == 'rejected' && !empty($b['return_rejection_reason'])): ?>
                    <div class="rejection-box">
                        <div class="rejection-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Alasan Penolakan Pengembalian
                        </div>
                        <div class="rejection-content"><?= nl2br(htmlspecialchars($b['return_rejection_reason'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Return Request Info -->
                    <?php if ($b['return_request_date']): ?>
                    <div class="return-info-box">
                        <div class="return-info-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Tanggal Pengajuan Pengembalian
                        </div>
                        <div class="return-info-content">
                            <?= date('d M Y H:i', strtotime($b['return_request_date'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Completed Status Info -->
                    <?php if ($b['status'] == 'Selesai'): ?>
                    <div class="completed-box">
                        <div class="completed-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Status Pengembalian
                        </div>
                        <div class="completed-content">
                            Pengembalian barang telah diselesaikan. Terima kasih telah mengembalikan barang tepat waktu.
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Items Grid -->
                    <h3 class="section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Barang yang Dipinjam (<?= count($b['items']) ?>)
                    </h3>

                    <div class="items-grid">
                        <?php foreach ($b['items'] as $item): ?>
                        <div class="item-card">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../gambar_item/<?= htmlspecialchars($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     class="item-image"
                                     onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                            <?php endif; ?>
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-meta">
                                    <span class="item-type"><?= htmlspecialchars($item['type']) ?></span>
                                    <?php if (!empty($item['category'])): ?>
                                        <span class="item-category"><?= htmlspecialchars($item['category']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity">Jumlah: <?= $item['qty'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effect to cards
            const cards = document.querySelectorAll('.borrowing-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 30px rgba(0,0,0,0.12)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>