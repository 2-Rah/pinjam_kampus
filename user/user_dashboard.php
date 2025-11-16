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

// Statistik peminjaman
function getCount($conn, $uid, $status = null) {
    if ($status === null) {
        $q = $conn->prepare("SELECT COUNT(*) AS c FROM borrowings WHERE user_id = ?");
        $q->bind_param("i", $uid);
    } else {
        $q = $conn->prepare("SELECT COUNT(*) AS c FROM borrowings WHERE user_id = ? AND status = ?");
        $q->bind_param("is", $uid, $status);
    }
    $q->execute();
    return $q->get_result()->fetch_assoc()['c'];
}

$pending = getCount($conn, $user_id, "pending");
$ongoing = getCount($conn, $user_id, "approved"); // atau "on_loan" jika pakai status itu
$finished = getCount($conn, $user_id, "returned");
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
            background:#2c3e50; color:white; padding:1rem 2rem;
            display:flex; justify-content:space-between; align-items:center;
        }
        .navbar a { color:white; text-decoration:none; }

        .container { max-width:1100px; margin:auto; padding:20px; }

        .stats-grid {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:20px; margin-top:20px;
        }
        .stat-box {
            background:white; padding:20px; border-radius:10px;
            box-shadow:0 3px 8px rgba(0,0,0,0.1);
            text-align:center;
        }
        .stat-box h2 { font-size:2.2rem; margin-bottom:10px; color:#2c3e50; }

        .menu-grid {
            margin-top:30px;
            display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
            gap:25px;
        }
        .menu-card {
            background:white; padding:30px; border-radius:12px;
            box-shadow:0 3px 8px rgba(0,0,0,0.1);
            text-align:center; text-decoration:none; color:#2c3e50;
            transition:0.25s;
        }
        .menu-card:hover {
            transform:translateY(-5px);
            box-shadow:0 5px 15px rgba(0,0,0,0.15);
        }
        .menu-card h3 { margin:10px 0; font-size:1.4rem; }
        .menu-card p { color:#7f8c8d; }

        .icon {
            font-size:3.5rem;
            margin-bottom:10px;
        }
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
    <p>Ringkasan peminjaman Anda:</p>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-box">
            <h2><?= $pending ?></h2>
            <p>Sedang Diajukan</p>
        </div>

        <div class="stat-box">
            <h2><?= $ongoing ?></h2>
            <p>Belum Dikembalikan</p>
        </div>

        <div class="stat-box">
            <h2><?= $finished ?></h2>
            <p>Sudah Selesai</p>
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
            <p>Lihat riwayat dan status peminjamanmu</p>
        </a>
    </div>

</div>

</body>
</html>
