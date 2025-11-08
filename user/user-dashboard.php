<?php
session_start();
require '../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Ambil statistik peminjaman user
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM borrowings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_borrowings = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Peminjaman</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .navbar h1 { font-size: 1.5rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .logout-btn:hover { background: #c0392b; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .welcome { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: flex; gap: 1rem; margin: 1rem 0; }
        .stat-card { background: #3498db; color: white; padding: 1rem; border-radius: 6px; flex: 1; text-align: center; }
        .menu-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; }
        .card { background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .card-icon { font-size: 3rem; margin-bottom: 1rem; text-align: center; }
        .card h3 { color: #2c3e50; margin-bottom: 0.5rem; text-align: center; }
        .card p { color: #7f8c8d; text-align: center; }
        .my-borrowings { background: white; padding: 2rem; border-radius: 8px; margin-top: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📦 Sistem Peminjaman Kampus</h1>
        <div class="user-info">
            <span>Halo, <strong><?= htmlspecialchars($user_name) ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome">
            <h2>Selamat Datang di Sistem Peminjaman</h2>
            <p>Silakan pilih kategori peminjaman yang Anda butuhkan</p>
            <div class="stats">
                <div class="stat-card">
                    <h3><?= $total_borrowings ?></h3>
                    <p>Total Peminjaman</p>
                </div>
            </div>
        </div>

        <div class="menu-cards">
            <a href="barang_list.php" class="card">
                <div class="card-icon">📦</div>
                <h3>Peminjaman Barang</h3>
                <p>Pinjam laptop, proyektor, kamera, dan peralatan elektronik lainnya</p>
            </a>

            <a href="ruangan_list.php" class="card">
                <div class="card-icon">🏢</div>
                <h3>Peminjaman Ruangan</h3>
                <p>Booking ruangan kelas, aula, lab, dan ruang rapat</p>
            </a>
        </div>

        <div class="my-borrowings">
            <h3>Peminjaman Saya</h3>
            <p><a href="my_borrowings.php" style="color: #3498db;">Lihat riwayat peminjaman →</a></p>
        </div>
    </div>
</body>
</html>