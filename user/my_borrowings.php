<?php
session_start();
require '../config.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* ==========================================================
   HANDLE HAPUS PENGAJUAN (hanya pending)
   ========================================================== */
if (isset($_GET['cancel'])) {
    $cancel_id = intval($_GET['cancel']);

    // Hanya hapus kalau status pending
    $check = $conn->prepare("SELECT status FROM borrowings WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $cancel_id, $user_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if ($res && $res['status'] === 'pending') {
        // delete borrowings → otomatis delete borrowing_details (ON DELETE CASCADE)
        $del = $conn->prepare("DELETE FROM borrowings WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $cancel_id, $user_id);
        $del->execute();

        header("Location: my_borrowings.php?deleted=1");
        exit;
    }
}

/* ==========================================================
   AMBIL SEMUA PEMINJAMAN USER + DETAIL ITEM + REJECTION REASON + PICKUP LOCATION
   PERBAIKAN: Tambah field "title" untuk judul peminjaman
   ========================================================== */
$sql = "
    SELECT 
        b.id AS borrowing_id,
        b.title AS borrowing_title,
        b.description,
        b.start_date,
        b.end_date,
        b.status,
        b.rejection_reason,
        b.pickup_location,
        b.approved_at,
        b.approved_by,
        admin.name AS approved_by_name,
        i.name AS item_name,
        i.image AS item_image,
        i.type AS item_type,
        i.category AS item_category,
        bd.quantity
    FROM borrowings b
    JOIN borrowing_details bd ON bd.borrowing_id = b.id
    JOIN items i ON i.id = bd.item_id
    LEFT JOIN users admin ON admin.id = b.approved_by
    WHERE b.user_id = ?
    ORDER BY b.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

/* ==========================================================
   UBAH MENJADI STRUKTUR TERKELOMPOK
   ========================================================== */
$borrowings = [];

while ($row = $result->fetch_assoc()) {
    $bid = $row['borrowing_id'];

    if (!isset($borrowings[$bid])) {
        // Gunakan "title" sebagai judul utama, jika kosong gunakan alternatif
        $borrowing_title = $row['borrowing_title'];
        if (empty($borrowing_title)) {
            $borrowing_title = "Peminjaman #" . $bid;
        }
        
        $borrowings[$bid] = [
            "title" => $borrowing_title,
            "description" => $row['description'],
            "start_date"  => $row['start_date'],
            "end_date"    => $row['end_date'],
            "status"      => $row['status'],
            "rejection_reason" => $row['rejection_reason'],
            "pickup_location" => $row['pickup_location'],
            "approved_at" => $row['approved_at'],
            "approved_by_name" => $row['approved_by_name'],
            "items"       => []
        ];
    }

    // masukkan item ke array
    $borrowings[$bid]["items"][] = [
        "name"     => $row['item_name'],
        "image"    => $row['item_image'],
        "type"     => $row['item_type'],
        "category" => $row['item_category'],
        "quantity" => $row['quantity']
    ];
}

// Status color mapping
$status_colors = [
    'pending' => '#f59e0b',
    'approved' => '#10b981',
    'rejected' => '#ef4444',
    'picked_up' => '#3b82f6',
    'completed' => '#8b5cf6',
    'not_returned' => '#dc2626'
];

$status_text = [
    'pending' => 'Menunggu Persetujuan',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    'picked_up' => 'Sedang Dipinjam',
    'completed' => 'Selesai',
    'not_returned' => 'Belum Dikembalikan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Saya • Sistem Peminjaman</title>
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

        /* NAVBAR - TANPA LINK CART */
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
        .navbar-links a[href="my_borrowings.php"]::after,
        .navbar-links a[href="my_borrowings.php"]:hover::after {
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

        /* Success Messages */
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
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

        .empty-state .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            text-decoration: none;
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

        .borrowing-title {
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

        .borrowing-id-section {
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

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
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

        /* Rejection Info Box */
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

        /* Approval Info Box */
        .approval-box {
            background: #f0fdf4;
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
            margin-bottom: 20px;
        }

        .approval-title {
            font-size: 14px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .approval-content {
            font-size: 14px;
            color: #166534;
            line-height: 1.6;
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR TANPA LINK CART -->
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
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                Peminjaman Saya
            </h1>
            <p class="page-subtitle">Lihat status dan riwayat semua peminjaman Anda</p>
        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Peminjaman berhasil dibatalkan.
            </div>
        <?php endif; ?>

        <?php if (empty($borrowings)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Belum Ada Peminjaman</h3>
                <p>Anda belum membuat pengajuan peminjaman apapun</p>
                <a href="user_items.php" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Mulai Peminjaman
                </a>
            </div>

        <?php else: ?>
            <!-- Borrowing List -->
            <?php foreach ($borrowings as $id => $b): 
                $status_color = $status_colors[$b['status']] ?? '#6b7280';
                $status_text_display = $status_text[$b['status']] ?? ucfirst($b['status']);
                
                // Hitung durasi
                $start = new DateTime($b['start_date']);
                $end = new DateTime($b['end_date']);
                $duration = $start->diff($end)->days + 1;
            ?>
            <div class="borrowing-card">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="borrowing-info">
                        <div class="borrowing-title">
                            <!-- JUDUL UTAMA PEMINJAMAN -->
                            <div class="borrowing-main-title"><?= htmlspecialchars($b['title']) ?></div>
                            
                            <div class="borrowing-id-section">
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
                                Mulai: <?= date('d M Y', strtotime($b['start_date'])) ?>
                            </div>
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="date-icon">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Selesai: <?= date('d M Y', strtotime($b['end_date'])) ?>
                            </div>
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="date-icon">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                </svg>
                                Durasi: <?= $duration ?> hari
                            </div>
                        </div>
                    </div>

                    <div class="status-section">
                        <div class="status-badge" style="background: <?= $status_color ?>20; color: <?= $status_color ?>;">
                            <span class="status-indicator" style="background: <?= $status_color ?>;"></span>
                            <?= $status_text_display ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($b['status'] === 'pending'): ?>
                                <a href="my_borrowings.php?cancel=<?= $id ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Yakin batalkan pengajuan ini?')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6L6 18M6 6l12 12"></path>
                                    </svg>
                                    Batalkan
                                </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($b['status'], ['approved', 'picked_up', 'completed'])): ?>
                                <a href="generate_pdf.php?id=<?= $id ?>" 
                                   class="btn btn-secondary" target="_blank">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                    Cetak Surat
                                </a>
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

                    <!-- Rejection Reason (jika status rejected) -->
                    <?php if ($b['status'] === 'rejected' && !empty($b['rejection_reason'])): ?>
                    <div class="rejection-box">
                        <div class="rejection-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Alasan Penolakan
                        </div>
                        <div class="rejection-content"><?= nl2br(htmlspecialchars($b['rejection_reason'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Approval Info (jika status approved/picked_up/completed) -->
                    <?php if (in_array($b['status'], ['approved', 'picked_up', 'completed'])): ?>
                    <div class="approval-box">
                        <div class="approval-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Disetujui
                        </div>
                        <div class="approval-content">
                            <?php if (!empty($b['approved_at'])): ?>
                                Tanggal: <?= date('d M Y H:i', strtotime($b['approved_at'])) ?>
                            <?php endif; ?>
                            <?php if (!empty($b['approved_by_name'])): ?>
                                • Oleh: <?= htmlspecialchars($b['approved_by_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Pickup Location (jika ada) -->
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
                            <img src="../gambar_item/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 class="item-image"
                                 onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-meta">
                                    <span class="item-type"><?= htmlspecialchars($item['type']) ?></span>
                                    <?php if (!empty($item['category'])): ?>
                                        <span class="item-category"><?= htmlspecialchars($item['category']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-quantity">Jumlah: <?= $item['quantity'] ?></div>
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