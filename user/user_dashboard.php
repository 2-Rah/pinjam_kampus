<?php
session_start();
require '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

/* =============================
   FUNGSI HITUNG STATUS
   ============================= */

// 1. Sedang diajukan
$sedang_diajukan = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'pending'
")->fetch_assoc()['c'];

// 2. Pengajuan ditolak
$pengajuan_ditolak = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'rejected'
")->fetch_assoc()['c'];

// 3. Belum diambil
$belum_diambil = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'approved'
")->fetch_assoc()['c'];

// 4. Belum dikembalikan (picked_up namun belum ada pengajuan return)
$belum_dikembalikan = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings b
    WHERE b.user_id = $user_id
      AND b.status = 'picked_up'
      AND NOT EXISTS (SELECT 1 FROM returns r WHERE r.borrowing_id = b.id)
")->fetch_assoc()['c'];

// 5. Pengembalian sedang diperiksa
$pengembalian_diperiksa = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'pending'
")->fetch_assoc()['c'];

// 6. Pengembalian ditolak
$pengembalian_ditolak = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'rejected'
")->fetch_assoc()['c'];

// 7. Peminjaman selesai
$peminjaman_selesai = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'approved'
")->fetch_assoc()['c'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Sistem Peminjaman</title>

<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:Arial; background:#f4f6f9; padding-bottom:50px; }

    .navbar {
        background:#2c3e50; 
        color:white; 
        padding:1rem 2rem;
        display:flex; 
        justify-content:space-between; 
        align-items:center;
    }
    .navbar a { color:white; text-decoration:none; }

    .container { max-width:1100px; margin:auto; padding:20px; }

    /* SINGLE BAR STATUS */
    .status-bar {
        background:white;
        padding:15px;
        border-radius:12px;
        box-shadow:0 3px 8px rgba(0,0,0,0.1);
        margin-top:20px;
        display:flex;
        flex-wrap:wrap;
        justify-content:space-between;
    }

    .status-box {
        text-align:center;
        margin:10px;
        min-width:120px;
    }

    .status-number {
        font-size:2rem;
        font-weight:bold;
        color:#2c3e50;
    }

    .status-label {
        color:#7f8c8d;
    }

    .menu-grid {
        margin-top:30px;
        display:grid; 
        grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
        gap:25px;
    }

    .menu-card {
        background:white; padding:30px; border-radius:12px;
        box-shadow:0 3px 8px rgba(0,0,0,0.1);
        text-align:center; 
        text-decoration:none; 
        color:#2c3e50;
        transition:0.25s;
    }
    .menu-card:hover {
        transform:translateY(-5px);
        box-shadow:0 5px 15px rgba(0,0,0,0.15);
    }
    .icon { font-size:3.5rem; margin-bottom:10px; }
</style>

</head>
<body>

<nav class="navbar">
    <h2>📦 Sistem Peminjaman Kampus</h2>
    <div>
        Halo, <strong><?= htmlspecialchars($user_name) ?></strong>
        &nbsp;|&nbsp;
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">

    <h2>Dashboard</h2>
    <p>Status Peminjaman Anda:</p>

    <!-- STATUS BAR -->
    <div class="status-bar">

        <div class="status-box">
            <div class="status-number"><?= $sedang_diajukan ?></div>
            <div class="status-label">Sedang Diajukan</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $pengajuan_ditolak ?></div>
            <div class="status-label">Pengajuan Ditolak</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $belum_diambil ?></div>
            <div class="status-label">Belum Diambil</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $belum_dikembalikan ?></div>
            <div class="status-label">Belum Dikembalikan</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $pengembalian_diperiksa ?></div>
            <div class="status-label">Pengembalian Diperiksa</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $pengembalian_ditolak ?></div>
            <div class="status-label">Pengembalian Ditolak</div>
        </div>

        <div class="status-box">
            <div class="status-number"><?= $peminjaman_selesai ?></div>
            <div class="status-label">Peminjaman Selesai</div>
        </div>

    </div>

    <!-- MENU GRID -->
    <div class="menu-grid">

        <a href="barang_list.php" class="menu-card">
            <div class="icon">📝</div>
            <h3>Ajukan Peminjaman</h3>
            <p>Pilih barang yang ingin kamu pinjam</p>
        </a>

        <a href="my_borrowings.php" class="menu-card">
            <div class="icon">📚</div>
            <h3>Peminjaman Saya</h3>
            <p>Lihat riwayat dan status</p>
        </a>

        <a href="user_return_selection.php" class="menu-card">
            <div class="icon">🔄</div>
            <h3>Pengembalian Barang</h3>
            <p>Kembalikan barang yang sedang kamu pinjam</p>
        </a>

    </div>

</div>

</body>
</html>
