<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}


$cart = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Keranjang Peminjaman</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; }
        img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
        .btn { padding: 8px 12px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>

<h2>Keranjang Peminjaman</h2>

<?php if (empty($cart)): ?>
    <p>Keranjang masih kosong.</p>
    <a href="barang_list.php" class="btn">Pilih Barang</a>

<?php else: ?>

<table>
    <tr>
        <th>Barang</th>
        <th>Nama</th>
        <th>Jumlah</th>
        <th>Stok</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($cart as $c): ?>
        <tr>
            <td><img src="../gambar_item/<?= $c['image'] ?>"></td>
            <td><?= $c['name'] ?></td>
            <td><?= $c['quantity'] ?></td>
            <td><?= $c['stock'] ?></td>
            <td>
                <a class="btn-danger btn" href="cart_remove.php?id=<?= $c['id'] ?>">Hapus</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<br>

<a href="barang_list.php" class="btn">Tambah Barang</a>
<a href="pinjam_form.php" class="btn" style="background:#198754">Lanjut Form Peminjaman</a>

<?php endif; ?>

</body>
</html>
