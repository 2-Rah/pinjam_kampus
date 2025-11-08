<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admin_dashboard.php');
    exit;
}
if (isset($_SESSION['user_id'])) {
    header('Location: user/user-dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Peminjaman Barang & Ruangan</title>
    <style>
        body {
            font-family: Arial;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        h2 { color: #667eea; margin-bottom: 30px; }
        p { color: #666; margin-bottom: 30px; }
        .button-group { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        button { padding: 12px 30px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .btn-admin { background-color: #667eea; color: white; }
        .btn-admin:hover { background-color: #5568d3; }
        .btn-user { background-color: #764ba2; color: white; }
        .btn-user:hover { background-color: #653a8a; }
        .btn-register { background-color: #f59e0b; color: white; }
        .btn-register:hover { background-color: #d97706; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Selamat Datang</h1>
        <h2>Sistem Peminjaman Barang & Ruangan</h2>
        <p>Platform terpadu untuk peminjaman barang kampus dan ruangan</p>
        
        <div class="button-group">
            <a href="admin/admin_login.php"><button class="btn-admin">🔐 Login Admin</button></a>
            <a href="user/user-login.php"><button class="btn-user">👤 Login User</button></a>
            <a href="registrasi.php"><button class="btn-register">📝 Daftar</button></a>
        </div>
    </div>
</body>
</html>
