<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}


$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uid = $_SESSION['user_id'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $desc = $_POST['description'];
    $pickup = $_POST['pickup_location'];

    // Insert ke borrowings
    $stmt = $conn->prepare("
        INSERT INTO borrowings (user_id, start_date, end_date, description, pickup_location, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("issss", $uid, $start, $end, $desc, $pickup);
    $stmt->execute();

    $borrowing_id = $stmt->insert_id;

    // Insert detail untuk setiap item
    $stmt2 = $conn->prepare("
        INSERT INTO borrowing_details (borrowing_id, item_id, quantity)
        VALUES (?, ?, ?)
    ");

    foreach ($cart as $c) {
        $stmt2->bind_param("iii", $borrowing_id, $c['id'], $c['quantity']);
        $stmt2->execute();
    }

    // Bersihkan keranjang
    $_SESSION['cart'] = [];

    header("Location: detail_peminjaman.php?id=" . $borrowing_id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Peminjaman</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        table { width:100%; background:white; border-collapse:collapse; margin-bottom:20px; }
        th, td { padding:12px; border-bottom:1px solid #ddd; }
        img { width:70px; height:70px; object-fit:cover; border-radius:6px; }
        form { background:white; padding:20px; border-radius:10px; max-width:500px; margin:auto; }
        input, textarea { width:100%; padding:10px; margin-top:10px; }
        .btn { padding:10px 14px; background:#198754; border:none; color:white; cursor:pointer; border-radius:6px; margin-top:15px; }
    </style>
</head>
<body>

<h2>Konfirmasi Barang yang Dipinjam</h2>

<table>
    <tr>
        <th>Gambar</th>
        <th>Nama Barang</th>
        <th>Jumlah</th>
    </tr>

    <?php foreach ($cart as $c): ?>
    <tr>
        <td><img src="../gambar_item/<?= $c['image'] ?>"></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['quantity']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<form method="POST">

    <label>Tanggal Mulai</label>
    <input type="date" name="start_date" required>

    <label>Tanggal Selesai</label>
    <input type="date" name="end_date" required>

    <label>Deskripsi</label>
    <textarea name="description" required></textarea>

    <label>Lokasi Pengambilan</label>
    <input type="text" name="pickup_location" required>

    <button class="btn">Kirim Peminjaman</button>

</form>

</body>
</html>
