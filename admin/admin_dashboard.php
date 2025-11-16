<?php
session_start();
require '../config.php';

// middleware protection: hanya admin yang boleh akses
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$admin_name = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Peminjaman</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; }

        .navbar { background:#2c3e50; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
        .navbar h1 { font-size:1.5rem; }

        .user-info { display:flex; align-items:center; gap:1rem; }
        .logout-btn { background:#e74c3c; color:white; padding:0.5rem 1rem; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
        .logout-btn:hover { background:#c0392b; }

        .container { max-width:1200px; margin:2rem auto; padding:0 2rem; }
        .welcome { background:white; padding:2rem; border-radius:8px; margin-bottom:2rem; box-shadow:0 2px 4px rgba(0,0,0,0.1); }

        .menu-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px,1fr)); gap:2rem; margin-top:2rem; }
        .card { background:white; border-radius:8px; padding:2rem; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:0.3s; text-decoration:none; color:inherit; display:block; }
        .card:hover { transform:translateY(-5px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }

        .card h3 { color:#2c3e50; margin-bottom:0.5rem; text-align:center; }
        .card p { color:#7f8c8d; text-align:center; }
        .card-icon { font-size:3rem; margin-bottom:1rem; text-align:center; }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>📦 Admin Sistem Peminjaman Kampus</h1>
        <div class="user-info">
            <span>Halo, <strong><?= htmlspecialchars($admin_name) ?></strong> (Admin)</span>
            <a href="logout_admin.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome">
            <h2>Selamat Datang Admin</h2>
            <p>Silakan pilih menu untuk mengelola sistem</p>
        </div>

        <div class="menu-cards">
            <a href="admin_manage_role.php" class="card">
                <div class="card-icon">👥</div>
                <h3>Manajemen User</h3>
                <p>Kelola role user dan pendaftaran user baru</p>
            </a>

            <a href="admin_manage_items.php" class="card">
                <div class="card-icon">📦</div>
                <h3>Kelola Barang</h3>
                <p>Tambah dan edit daftar barang yang bisa dipinjam</p>
            </a>

            <a href="admin_manage_borrowings.php" class="card">
                <div class="card-icon">✅</div>
                <h3>Peminjaman Barang</h3>
                <p>Setujui / tolak peminjaman</p>
            </a>

        </div>
    </div>

</body>
</html>