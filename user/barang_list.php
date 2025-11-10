<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ambil barang dari DB
$data = $conn->query("SELECT * FROM items WHERE is_active=1");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Peminjaman Barang</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <h2 class="title">Peminjaman Barang</h2>

    <form action="pinjam_form.php" method="POST">
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php while($b = $data->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="item_id[]" value="<?= $b['id'] ?>"></td>
                <td><?= htmlspecialchars($b['name']) ?></td>
                <td><?= htmlspecialchars($b['category']) ?></td>
                <td><?= htmlspecialchars($b['stock']) ?></td>
                <td></td>
            </tr>
        <?php endwhile ?>
        </tbody>
    </table>

    <br>
    <button type="submit" class="btn">Ajukan</button>
    </form>
</div>
</body>
</html>
