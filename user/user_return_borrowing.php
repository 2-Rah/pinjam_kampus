<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borrow_id = $_GET['borrow_id'] ?? null;

if (!$borrow_id) {
    die("Borrowing ID tidak ditemukan!");
}

/* ===========================
   CEK APAKAH RETURN SUDAH ADA
   =========================== */
$check = mysqli_query($conn, "SELECT * FROM returns WHERE borrowing_id=$borrow_id");
$return_row = mysqli_fetch_assoc($check);
$return_id = $return_row['id'] ?? null;

/* ==============================
   HANDLE FORM CONFIRM RETURN
   ============================== */
if (isset($_POST['confirm_return'])) {
    $detail_id = $_POST['detail_id'];
    $item_id = $_POST['item_id'];
    $qty = intval($_POST['qty']);
    $condition = $_POST['condition'];

    $error = null;
    
    if ($qty <= 0) {
        $error = "Quantity tidak boleh nol!";
    }

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
        $error = "Foto wajib diupload!";
    }

    if (!isset($error)) {
        // buat return record kalau belum ada
        if (!$return_id) {
            mysqli_query($conn, "
                INSERT INTO returns (borrowing_id, user_id, return_date, status)
                VALUES ($borrow_id, $user_id, CURDATE(), 'pending')
            ");
            $return_id = mysqli_insert_id($conn);
        }

        // upload foto
        $folder = "pengembalian_barang/";
        $filename = time() . "_" . basename($_FILES['photo']['name']);
        $path = $folder . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $path);

        // insert return_details
        mysqli_query($conn, "
            INSERT INTO return_details 
            (return_id, item_id, borrowing_detail_id, quantity, item_condition, image, status)
            VALUES ($return_id, $item_id, $detail_id, $qty, '$condition', '$path', 'pending')
        ");

        $success = "Pengembalian berhasil disimpan! Menunggu persetujuan admin.";
    }
}

/* ==============================
   HANDLE EDIT RETURN DETAIL
   ============================== */
if (isset($_POST['edit_return'])) {
    $rd_id = $_POST['rd_id'];
    $qty = intval($_POST['qty']);
    $condition = $_POST['condition'];

    // Foto baru opsional
    $update_photo = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $folder = "pengembalian_barang/";
        $filename = time() . "_" . basename($_FILES['photo']['name']);
        $path = $folder . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $path);

        $update_photo = ", image='$path'";
    }

    mysqli_query($conn, "
        UPDATE return_details SET 
        quantity=$qty,
        item_condition='$condition',
        status='pending'
        $update_photo
        WHERE id=$rd_id
    ");

    $success = "Data pengembalian berhasil diperbarui! Menunggu persetujuan admin.";
}

/* ===========================
   AMBIL INFORMASI PEMINJAMAN
   =========================== */
$borrowing_info = mysqli_query($conn, "
    SELECT b.description, b.start_date, b.end_date
    FROM borrowings b
    WHERE b.id = $borrow_id AND b.user_id = $user_id
");
$borrowing = mysqli_fetch_assoc($borrowing_info);

/* ===========================
   AMBIL LIST BARANG DIPINJAM
   =========================== */
$data = mysqli_query($conn, "
    SELECT bd.id AS detail_id, bd.quantity AS borrowed_qty,
           i.id AS item_id, i.name, i.image, i.type, i.category
    FROM borrowing_details bd
    JOIN items i ON i.id = bd.item_id
    WHERE bd.borrowing_id = $borrow_id
");

/* ===========================
   AMBIL RETURN DETAIL JIKA ADA
   =========================== */
$returned_items = [];
if ($return_id) {
    $r = mysqli_query($conn, "
        SELECT * FROM return_details WHERE return_id=$return_id
    ");
    while ($row = mysqli_fetch_assoc($r)) {
        $returned_items[$row['borrowing_detail_id']] = $row;
    }
}

// Status condition mapping
$condition_labels = [
    'good' => 'Baik',
    'damaged' => 'Rusak',
    'lost' => 'Hilang',
    'needs_repair' => 'Perlu Perbaikan'
];

$condition_colors = [
    'good' => '#10b981',
    'damaged' => '#ef4444',
    'lost' => '#6b7280',
    'needs_repair' => '#f59e0b'
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

        /* Messages */
        .message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .error-message {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }

        .info-message {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
            margin-bottom: 30px;
        }

        /* Borrowing Info Card */
        .info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #e0e7ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
        }

        .info-content h4 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .info-content p {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Item Info */
        .item-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .item-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .item-details h4 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .item-meta {
            display: flex;
            gap: 8px;
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

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        /* Condition Badge */
        .condition-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .condition-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        /* Photo Preview */
        .photo-preview {
            position: relative;
            display: inline-block;
        }

        .photo-preview img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .photo-preview img:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }

        .photo-label {
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
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

            table {
                display: block;
                overflow-x: auto;
            }

            th, td {
                padding: 12px;
                white-space: nowrap;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            <p class="page-subtitle">Isi form pengembalian untuk setiap item yang dipinjam</p>
        </div>

        <!-- Messages -->
        <?php if (isset($success)): ?>
            <div class="message success-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error-message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Info Message -->
        <div class="message info-message">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Pastikan kondisi barang sesuai dengan keadaan sebenarnya. Foto akan diverifikasi oleh admin.
        </div>

        <!-- Borrowing Information -->
        <div class="info-card">
            <h3 style="margin-bottom: 20px; color: #1e293b; font-size: 18px;">
                Informasi Peminjaman #<?= htmlspecialchars($borrow_id) ?>
            </h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Deskripsi</h4>
                        <p><?= htmlspecialchars($borrowing['description'] ?? 'Tidak ada deskripsi') ?></p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Tanggal Mulai</h4>
                        <p><?= date('d M Y', strtotime($borrowing['start_date'])) ?></p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Tanggal Selesai</h4>
                        <p><?= date('d M Y', strtotime($borrowing['end_date'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="25%">Barang</th>
                        <th width="10%">Jumlah Pinjam</th>
                        <th width="15%">Jumlah Kembali</th>
                        <th width="15%">Kondisi</th>
                        <th width="20%">Foto Bukti</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                mysqli_data_seek($data, 0); // Reset pointer
                while ($d = mysqli_fetch_assoc($data)): 
                    $detail_id = $d['detail_id'];
                    $returned = $returned_items[$detail_id] ?? null;
                ?>
                    <tr>
                        <!-- Item Info -->
                        <td>
                            <div class="item-info">
                                <?php if (!empty($d['image'])): ?>
                                    <img src="../gambar_item/<?= htmlspecialchars($d['image']) ?>" 
                                         alt="<?= htmlspecialchars($d['name']) ?>"
                                         class="item-image"
                                         onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                                <?php endif; ?>
                                <div>
                                    <h4><?= htmlspecialchars($d['name']) ?></h4>
                                    <div class="item-meta">
                                        <span class="item-type"><?= htmlspecialchars($d['type']) ?></span>
                                        <?php if (!empty($d['category'])): ?>
                                            <span class="item-category"><?= htmlspecialchars($d['category']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Borrowed Quantity -->
                        <td>
                            <div style="font-weight: 600; font-size: 16px; color: #1e293b;">
                                <?= $d['borrowed_qty'] ?>
                            </div>
                        </td>

                        <!-- Form -->
                        <td colspan="4">
                            <?php if (!$returned): ?>
                                <!-- FORM INPUT RETURN BARU -->
                                <form method="POST" enctype="multipart/form-data">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: center;">
                                        <!-- Quantity -->
                                        <div class="form-group">
                                            <input type="number" 
                                                   name="qty" 
                                                   min="1" 
                                                   max="<?= $d['borrowed_qty'] ?>" 
                                                   value="<?= $d['borrowed_qty'] ?>"
                                                   required
                                                   class="form-control"
                                                   style="width: 100px;">
                                        </div>

                                        <!-- Condition -->
                                        <div class="form-group">
                                            <select name="condition" class="form-control" style="width: 150px;">
                                                <?php foreach ($condition_labels as $key => $label): ?>
                                                    <option value="<?= $key ?>"><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Photo -->
                                        <div class="form-group">
                                            <input type="file" 
                                                   name="photo" 
                                                   required
                                                   accept="image/*"
                                                   class="form-control"
                                                   style="padding: 8px; width: 200px;">
                                        </div>

                                        <!-- Submit Button -->
                                        <div>
                                            <input type="hidden" name="detail_id" value="<?= $detail_id ?>">
                                            <input type="hidden" name="item_id" value="<?= $d['item_id'] ?>">
                                            <button type="submit" 
                                                    name="confirm_return" 
                                                    class="btn btn-primary btn-sm">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 6L9 17l-5-5"></path>
                                                </svg>
                                                Ajukan Pengembalian
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            <?php else: ?>
                                <!-- SUDAH DIKIRIM → TAMPIL EDIT -->
                                <form method="POST" enctype="multipart/form-data">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: center;">
                                        <!-- Quantity -->
                                        <div class="form-group">
                                            <input type="number" 
                                                   name="qty" 
                                                   min="1" 
                                                   max="<?= $d['borrowed_qty'] ?>" 
                                                   value="<?= $returned['quantity'] ?>"
                                                   required
                                                   class="form-control"
                                                   style="width: 100px;">
                                        </div>

                                        <!-- Condition -->
                                        <div class="form-group">
                                            <select name="condition" class="form-control" style="width: 150px;">
                                                <?php foreach ($condition_labels as $key => $label): ?>
                                                    <option value="<?= $key ?>" <?= $returned['item_condition'] == $key ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Photo -->
                                        <div class="form-group">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="photo-preview">
                                                    <img src="<?= htmlspecialchars($returned['image']) ?>" 
                                                         alt="Bukti Pengembalian"
                                                         onclick="window.open('<?= htmlspecialchars($returned['image']) ?>', '_blank')"
                                                         style="cursor: pointer;">
                                                    <div class="photo-label">Klik untuk zoom</div>
                                                </div>
                                                <div>
                                                    <input type="file" 
                                                           name="photo" 
                                                           accept="image/*"
                                                           class="form-control"
                                                           style="padding: 8px; width: 180px;">
                                                    <small style="display: block; color: #64748b; margin-top: 4px;">Kosongkan jika tidak ingin mengganti</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div>
                                            <input type="hidden" name="rd_id" value="<?= $returned['id'] ?>">
                                            <button type="submit" 
                                                    name="edit_return" 
                                                    class="btn btn-warning btn-sm">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Perbarui
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Status Info -->
        <?php if ($return_id && mysqli_num_rows($data) > 0): ?>
            <div class="info-card">
                <h3 style="margin-bottom: 15px; color: #1e293b; font-size: 16px;">Status Pengembalian</h3>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #e0f2fe; border-radius: 20px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span style="font-weight: 500; color: #0369a1;">Status: Pending</span>
                    </div>
                    <p style="color: #64748b; font-size: 14px;">
                        Pengembalian Anda telah diajukan dan sedang menunggu persetujuan admin.
                        Anda dapat mengedit data pengembalian sebelum disetujui.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="user_return_selection.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                Kembali ke Daftar Peminjaman
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview photo before upload
            const fileInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'photo-preview';
                            preview.innerHTML = `
                                <img src="${e.target.result}" alt="Preview" style="width: 80px; height: 80px; border-radius: 8px; margin-top: 10px;">
                                <div class="photo-label">Preview</div>
                            `;
                            
                            // Remove existing preview
                            const existingPreview = input.parentElement.querySelector('.photo-preview');
                            if (existingPreview) {
                                existingPreview.remove();
                            }
                            
                            // Add new preview
                            input.parentElement.appendChild(preview);
                        }
                        reader.readAsDataURL(file);
                    }
                });
            });

            // Add animation to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'fadeIn 0.5s ease-out forwards';
                row.style.opacity = '0';
            });
        });
    </script>
</body>
</html>