<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_manage_borrowings.php");
    exit;
}

$id = intval($_GET['id']);

$q = $conn->query("
    SELECT b.*, u.name AS user_name
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = $id
");

$borrow = $q->fetch_assoc();
if (!$borrow) {
    echo "Data tidak ditemukan.";
    exit;
}

// ambil detail
$detail = $conn->query("
SELECT d.quantity, i.name
FROM borrowing_details d
JOIN items i ON d.item_id = i.id
WHERE d.borrowing_id = $id
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Peminjaman</title>
<style>
body { font-family:Arial; padding:20px; }
table { width:100%; border-collapse:collapse; }
td, th { border:1px solid #ccc; padding:8px; }
th { background:#eee; }
</style>
</head>
<body>

<h2>Detail Peminjaman #<?= $borrow['id'] ?></h2>

<p><strong>Peminjam:</strong> <?= htmlspecialchars($borrow['user_name']) ?></p>
<p><strong>Status:</strong> <?= $borrow['status'] ?></p>
<p><strong>Tanggal:</strong> <?= $borrow['created_at'] ?></p>

<h3>Barang Dipinjam</h3>

<table>
    <tr>
        <th>Nama Barang</th>
        <th>Jumlah</th>
    </tr>

    <?php while ($d = $detail->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($d['name']) ?></td>
        <td><?= $d['quantity'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<br>

<a href="admin_manage_borrowings.php">← Kembali</a>

</body>
</html>
