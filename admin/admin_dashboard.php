<?php
session_start();

// middleware protection: hanya admin yang boleh akses
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
    <h2>Halo, <?= htmlspecialchars($_SESSION['admin_name']) ?> (Admin)</h2>
    <p>Login berhasil ✅</p>

    <ul>
        <li><a href="admin_login.php">Halaman Login</a></li>
        <li><a href="logout_admin.php">Logout</a></li>
        <li><a href="admin_manage_role.php">Registrasi user</a></li>
        <li><a href="admin_manage_borrowings.php">peminjaman barang</a></li>
        <li><a href="admin_manage_items.php">atur barang</a></li>
    </ul>
</body>
</html>
