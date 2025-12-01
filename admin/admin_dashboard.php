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

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            color: #1a202c;
        }

        /* NAVBAR */
        .navbar {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            padding: 16px 32px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .navbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 600;
        }

        .navbar-title svg {
            width: 28px;
            height: 28px;
            fill: white;
        }

        .user-info {
            display:flex;
            align-items:center;
            gap: 14px;
            font-size: 15px;
            font-weight: 500;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #ff5b5b;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            text-decoration:none;
            transition: .3s;
            box-shadow: 0 4px 10px rgba(255,91,91,0.4);
        }

        .logout-btn:hover {
            background:#e04444;
            transform: translateY(-2px);
        }

        /* CONTAINER */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome {
            background:white;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            text-align: center;
            margin-bottom: 35px;
            animation: fadeIn 0.6s ease-out;
        }

        .welcome h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #64748b;
        }

        /* MENU CARDS */
        .menu-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .card {
            background:white;
            border-radius: 20px;
            padding: 28px;
            text-decoration:none;
            color: inherit;
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
            transition: 0.3s ease;
            animation: fadeIn 0.6s ease-out;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .card-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg,#667eea,#764ba2);
            border-radius: 16px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color:white;
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }

        .card-icon svg {
            width: 34px;
            height: 34px;
            fill: white;
        }

        .card h3 {
            font-size: 18px;
            font-weight: 700;
            text-align:center;
            margin-bottom: 8px;
            color:#1a202c;
        }

        .card p {
            text-align:center;
            color:#64748b;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from { opacity:0; transform: translateY(20px); }
            to { opacity:1; transform: translateY(0); }
        }

        @media(max-width:700px){
            .menu-cards { grid-template-columns: 1fr; }
        }
    </style>

</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-title">
            <svg viewBox="0 0 24 24">
                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>
            </svg>
            <span>Dashboard Admin</span>
        </div>

        <div class="user-info">
            Halo, <strong><?= htmlspecialchars($admin_name) ?></strong>
            <a href="logout_admin.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container">

        <div class="welcome">
            <h2>Selamat Datang Admin</h2>
            <p>Silakan pilih menu untuk mengelola sistem</p>
        </div>

        <div class="menu-cards">

            <!-- MANAGE USER -->
            <a href="admin_manage_role.php" class="card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 
                        0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 
                        1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 
                        0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 
                        3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </div>
                <h3>Manajemen User</h3>
                <p>Kelola role dan daftar user kampus</p>
            </a>

            <!-- MANAGE ITEMS -->
            <a href="admin_manage_items.php" class="card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 16V8c0-.55-.45-1-1-1h-1V5c0-.55-.45-1-1-1H6c-.55 
                        0-1 .45-1 1v2H4c-.55 0-1 .45-1 
                        1v8c0 .55.45 1 1 1h1v2c0 .55.45 
                        1 1 1h12c.55 0 1-.45 1-1v-2h1c.55 
                        0 1-.45 1-1zm-5 3H8v-1h8v1zm2-3H6V9h12v7z"/>
                    </svg>
                </div>
                <h3>Kelola Barang</h3>
                <p>Tambah, edit, dan monitor inventaris</p>
            </a>

            <!-- BORROWING -->
            <a href="admin_manage_borrowings.php" class="card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 
                        2v14c0 1.1.9 2 2 2h14c1.1 
                        0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 
                        16H5V5h14v14zM7 7h10v2H7zm0 
                        4h10v2H7zm0 4h7v2H7z"/>
                    </svg>
                </div>
                <h3>Peminjaman Barang</h3>
                <p>Review dan proses permintaan</p>
            </a>

            <!-- RETURN -->
            <a href="admin_manage_returns.php" class="card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 5V1L7 6l5 5V7c3.31 
                        0 6 2.69 6 6 0 1.01-.25 1.97-.69 
                        2.8l1.46 1.46C19.54 16.02 20 14.57 20 
                        13c0-4.42-3.58-8-8-8zm-6.31 
                        3.2L4.23 6.74C3.46 8 3 9.43 3 
                        11c0 4.42 3.58 8 8 8v4l5-5-5-5v4c-3.31 
                        0-6-2.69-6-6 0-1.01.25-1.97.69-2.8z"/>
                    </svg>
                </div>
                <h3>Kelola Pengembalian</h3>
                <p>Verifikasi dan catat pengembalian barang</p>
            </a>

        </div>
    </div>

</body>
</html>
