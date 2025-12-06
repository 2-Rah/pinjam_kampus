<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $desc = trim($_POST['description']);

    // Validasi
    if (empty($title)) {
        $errors[] = "Judul peminjaman wajib diisi";
    }
    
    if (empty($start) || empty($end)) {
        $errors[] = "Tanggal mulai dan selesai wajib diisi";
    } else {
        $today = date('Y-m-d');
        $start_date = new DateTime($start);
        $end_date = new DateTime($end);
        $today_date = new DateTime($today);
        
        // Validasi tanggal tidak boleh lampau
        if ($start_date < $today_date) {
            $errors[] = "Tanggal mulai tidak boleh tanggal yang sudah lewat";
        }
        
        // Validasi tanggal selesai harus lebih besar dari tanggal mulai
        if ($end_date <= $start_date) {
            $errors[] = "Tanggal selesai harus lebih dari tanggal mulai";
        }
        
        // Validasi durasi maksimal (misal 30 hari)
        $interval = $start_date->diff($end_date);
        $duration_days = $interval->days;
        
        if ($duration_days > 30) {
            $errors[] = "Durasi peminjaman maksimal 30 hari";
        }
        
        if ($duration_days < 1) {
            $errors[] = "Durasi peminjaman minimal 1 hari";
        }
    }

    if (empty($errors)) {
        // Insert ke borrowings dengan title dan tanpa pickup_location
        $stmt = $conn->prepare("
            INSERT INTO borrowings (user_id, title, start_date, end_date, description, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("issss", $uid, $title, $start, $end, $desc);
        $stmt->execute();

        $borrowing_id = $stmt->insert_id;

        // Insert detail untuk setiap item
        $stmt2 = $conn->prepare("
            INSERT INTO borrowing_details (borrowing_id, item_id, quantity)
            VALUES (?, ?, ?)
        ");

        foreach ($cart as $c) {
            $stmt2->bind_param("iii", $borrowing_id, $c['id'], $c['quantity']);
            $stmt2->execute();
        }

        // Bersihkan keranjang
        $_SESSION['cart'] = [];

        header("Location: detail_peminjaman.php?id=" . $borrowing_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman - Sistem Peminjaman Kampus</title>
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
            background: #f8f9fa;
            padding: 24px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg {
            width: 20px;
            height: 20px;
            fill: #667eea;
        }

        /* Alert Errors */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
            color: #991b1b;
        }

        .alert-error li {
            margin-bottom: 8px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
        }

        .items-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1a202c;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label .required {
            color: #ef4444;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-help {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }

        /* Date Info */
        .date-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 13px;
            color: #0c4a6e;
            display: none;
        }

        .date-info.show {
            display: block;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Form Peminjaman Barang</h1>
            <p>Lengkapi informasi peminjaman Anda dengan benar</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <strong>Terdapat kesalahan:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24">
                    <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/>
                </svg>
                Barang yang Dipinjam
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $c): ?>
                    <tr>
                        <td><img src="../gambar_item/<?= htmlspecialchars($c['image']) ?>" alt=""></td>
                        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                        <td><?= htmlspecialchars($c['quantity']) ?> item</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                </svg>
                Informasi Peminjaman
            </div>

            <form method="POST" id="borrowForm">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="title">
                            Judul Peminjaman <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required
                            placeholder="Contoh: Peminjaman untuk Praktikum Jaringan"
                            value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                        >
                        <div class="form-help">Berikan judul yang jelas untuk memudahkan identifikasi</div>
                    </div>

                    <div class="form-group">
                        <label for="start_date">
                            Tanggal Mulai <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            required
                            min="<?= date('Y-m-d') ?>"
                            value="<?= isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d') ?>"
                        >
                        <div class="form-help">Tanggal mulai peminjaman</div>
                    </div>

                    <div class="form-group">
                        <label for="end_date">
                            Tanggal Selesai <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            required
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                            value="<?= isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '' ?>"
                        >
                        <div class="form-help">Tanggal pengembalian (maksimal 30 hari)</div>
                    </div>

                    <div class="form-group full-width">
                        <div id="dateInfo" class="date-info"></div>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">
                            Keperluan / Deskripsi <span class="required">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            required
                            placeholder="Jelaskan keperluan peminjaman barang ini..."
                        ><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        <div class="form-help">Jelaskan untuk apa barang ini dipinjam</div>
                    </div>
                </div>

                <div class="button-group">
                    <a href="cart.php" class="btn btn-secondary">Kembali ke Keranjang</a>
                    <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const dateInfo = document.getElementById('dateInfo');

        function calculateDuration() {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                // Validasi tanggal mulai tidak boleh lampau
                if (start < today) {
                    dateInfo.className = 'date-info show';
                    dateInfo.style.background = '#fef2f2';
                    dateInfo.style.borderColor = '#fee2e2';
                    dateInfo.style.color = '#991b1b';
                    dateInfo.textContent = '⚠️ Tanggal mulai tidak boleh tanggal yang sudah lewat';
                    return;
                }

                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays < 1) {
                    dateInfo.className = 'date-info show';
                    dateInfo.style.background = '#fef2f2';
                    dateInfo.style.borderColor = '#fee2e2';
                    dateInfo.style.color = '#991b1b';
                    dateInfo.textContent = '⚠️ Durasi peminjaman minimal 1 hari';
                } else if (diffDays > 30) {
                    dateInfo.className = 'date-info show';
                    dateInfo.style.background = '#fef2f2';
                    dateInfo.style.borderColor = '#fee2e2';
                    dateInfo.style.color = '#991b1b';
                    dateInfo.textContent = '⚠️ Durasi peminjaman maksimal 30 hari (Anda memilih ' + diffDays + ' hari)';
                } else {
                    dateInfo.className = 'date-info show';
                    dateInfo.style.background = '#f0f9ff';
                    dateInfo.style.borderColor = '#bae6fd';
                    dateInfo.style.color = '#0c4a6e';
                    dateInfo.textContent = '✓ Durasi peminjaman: ' + diffDays + ' hari';
                }
            } else {
                dateInfo.className = 'date-info';
            }
        }

        startDate.addEventListener('change', function() {
            // Set min date untuk end_date
            const minEndDate = new Date(this.value);
            minEndDate.setDate(minEndDate.getDate() + 1);
            endDate.min = minEndDate.toISOString().split('T')[0];
            
            calculateDuration();
        });

        endDate.addEventListener('change', calculateDuration);

        // Form validation before submit
        document.getElementById('borrowForm').addEventListener('submit', function(e) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (start < today) {
                e.preventDefault();
                alert('Tanggal mulai tidak boleh tanggal yang sudah lewat');
                return false;
            }

            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays < 1) {
                e.preventDefault();
                alert('Durasi peminjaman minimal 1 hari');
                return false;
            }

            if (diffDays > 30) {
                e.preventDefault();
                alert('Durasi peminjaman maksimal 30 hari');
                return false;
            }
        });
    </script>
</body>
</html>