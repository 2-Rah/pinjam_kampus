<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID peminjaman tidak ditemukan.");
}

$id = $_GET['id'];

// ambil data utama peminjaman + user
$q = $conn->prepare("
    SELECT b.id, b.start_date, b.end_date, b.description, b.pickup_location, b.status,
           b.judul, b.title,
           u.name AS user_name
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$q->bind_param("i", $id);
$q->execute();
$main = $q->get_result()->fetch_assoc();

if (!$main) {
    die("Data peminjaman tidak ditemukan.");
}

// ambil item-item yang dipinjam
$q2 = $conn->prepare("
    SELECT d.quantity, i.name, i.image, i.category, i.type
    FROM borrowing_details d
    JOIN items i ON d.item_id = i.id
    WHERE d.borrowing_id = ?
");
$q2->bind_param("i", $id);
$q2->execute();
$items = $q2->get_result();

// Hitung jumlah item
$total_items = $items->num_rows;

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

// Hitung durasi peminjaman
$start = new DateTime($main['start_date']);
$end = new DateTime($main['end_date']);
$duration = $start->diff($end)->days + 1;

// Tentukan judul peminjaman
$judul_peminjaman = '';
if (!empty($main['judul'])) {
    $judul_peminjaman = $main['judul'];
} elseif (!empty($main['title'])) {
    $judul_peminjaman = $main['title'];
} else {
    $judul_peminjaman = "Peminjaman #" . $main['id'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman • Sistem Peminjaman</title>
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

        /* ACTIVE LINK: My Borrowings */
        .navbar-links a[href="my_borrowings.php"]::after,
        .navbar-links a[href="my_borrowings.php"]:hover::after {
            transform: scaleX(1);
        }

        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 32px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #64748b;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #764ba2;
        }

        .breadcrumb .separator {
            color: #cbd5e0;
        }

        /* Header Card */
        .header-card {
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

        .header-card::before {
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

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .loan-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            backdrop-filter: blur(10px);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: <?= $status_colors[$main['status']] ?>;
            animation: pulse 2s infinite;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        /* Main Card */
        .main-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease-out;
        }

        /* Judul Peminjaman */
        .judul-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }

        .judul-label {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .judul-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        /* Informasi Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        }

        .info-label {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label svg {
            stroke: #667eea;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Deskripsi Section */
        .desc-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .desc-label {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .desc-content {
            font-size: 15px;
            color: #475569;
            line-height: 1.7;
            white-space: pre-line;
        }

        .desc-empty {
            color: #94a3b8;
            font-style: italic;
        }

        /* Items Section */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 30px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title svg {
            stroke: #667eea;
        }

        .items-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 24px;
        }

        .item-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 16px;
            background: white;
        }

        .item-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        }

        .item-card img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            font-size: 16px;
        }

        .item-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }

        .item-type {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .item-category {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .item-quantity {
            font-size: 14px;
            color: #64748b;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Action Button */
        .action-button {
            display: flex;
            justify-content: center;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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

            .header-card {
                padding: 20px;
            }

            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .main-card {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .judul-value {
                font-size: 20px;
            }

            .items-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .action-button {
                padding: 15px;
            }

            .btn {
                width: 100%;
                justify-content: center;
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-brand">Sistem Peminjaman - User</div>
        <div class="navbar-links">
            <a href="user_dashboard.php">Dashboard</a>
            <a href="user_items.php">Daftar Barang</a>
            <a href="cart.php">Keranjang</a>
            <a href="my_borrowings.php">Peminjaman Saya</a>
            <a href="user_return_selection.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="my_borrowings.php">Peminjaman Saya</a>
            <span class="separator">›</span>
            <span>Detail Peminjaman #<?= $main['id'] ?></span>
        </div>

        <!-- Header Card -->
        <div class="header-card">
            <div class="header-top">
                <div class="loan-id">ID: #<?= str_pad($main['id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div class="status-badge">
                    <span class="status-indicator"></span>
                    <?= isset($status_text[$main['status']]) ? $status_text[$main['status']] : ucfirst($main['status']) ?>
                </div>
            </div>
            <h1 class="header-title">Detail Peminjaman</h1>
            <p class="header-subtitle"><?= $total_items ?> barang dipinjam • <?= $duration ?> hari</p>
        </div>

        <!-- Main Card -->
        <div class="main-card">
            <!-- Judul Peminjaman -->
            <div class="judul-section">
                <div class="judul-label">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Judul Peminjaman
                </div>
                <div class="judul-value"><?= htmlspecialchars($judul_peminjaman) ?></div>
            </div>

            <!-- Informasi Peminjaman -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Nama Peminjam
                    </div>
                    <div class="info-value"><?= htmlspecialchars($main['user_name']) ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Tanggal Mulai
                    </div>
                    <div class="info-value"><?= date('d M Y', strtotime($main['start_date'])) ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Tanggal Selesai
                    </div>
                    <div class="info-value"><?= date('d M Y', strtotime($main['end_date'])) ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                        </svg>
                        Durasi
                    </div>
                    <div class="info-value"><?= $duration ?> Hari</div>
                </div>
            </div>

            <!-- Deskripsi Peminjaman -->
            <div class="desc-section">
                <div class="desc-label">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Deskripsi Peminjaman
                </div>
                <div class="desc-content <?= empty($main['description']) ? 'desc-empty' : '' ?>">
                    <?= !empty($main['description']) ? nl2br(htmlspecialchars($main['description'])) : 'Tidak ada deskripsi' ?>
                </div>
            </div>

            <!-- Items Section -->
            <h2 class="section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Barang yang Dipinjam (<?= $total_items ?>)
            </h2>

            <div class="items-container">
                <div class="items-grid">
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <div class="item-card">
                        <img src="../gambar_item/<?= htmlspecialchars($item['image']) ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
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
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="action-button">
            <a href="my_borrowings.php" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                Kembali ke Daftar Peminjaman
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effect to item cards
            const itemCards = document.querySelectorAll('.item-card');
            itemCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '';
                });
            });
        });
    </script>
</body>
</html>