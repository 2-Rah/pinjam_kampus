<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}


if (!isset($_GET['id'])) {
    die("ID peminjaman tidak ditemukan.");
}

$id = $_GET['id'];

// ambil data utama peminjaman + user
$q = $conn->prepare("
    SELECT b.id, b.start_date, b.end_date, b.description, b.pickup_location, b.status,
           u.name AS user_name
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$q->bind_param("i", $id);
$q->execute();
$main = $q->get_result()->fetch_assoc();

if (!$main) {
    die("Data peminjaman tidak ditemukan.");
}

// ambil item-item yang dipinjam
$q2 = $conn->prepare("
    SELECT d.quantity, i.name, i.image
    FROM borrowing_details d
    JOIN items i ON d.item_id = i.id
    WHERE d.borrowing_id = ?
");
$q2->bind_param("i", $id);
$q2->execute();
$items = $q2->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Peminjaman</title>
    <style>
        body { font-family: Arial; padding:20px; background:#f2f2f2; }

        .card {
            background:white;
            padding:20px;
            border-radius:10px;
            max-width:700px;
            margin:auto;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        .item-box {
            display:flex;
            gap:15px;
            padding:10px;
            border-bottom:1px solid #eee;
        }
        img {
            width:80px;
            height:80px;
            object-fit:cover;
            border-radius:8px;
        }
        h3 { margin-top:30px; }
        .btn {
            background:#0d6efd;
            padding:10px 14px;
            color:white;
            text-decoration:none;
            border-radius:6px;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Detail Peminjaman</h2>

    <p><b>ID Peminjaman:</b> <?= $main['id'] ?></p>
    <p><b>Nama Peminjam:</b> <?= $main['user_name'] ?></p>
    <p><b>Tanggal Mulai:</b> <?= $main['start_date'] ?></p>
    <p><b>Tanggal Selesai:</b> <?= $main['end_date'] ?></p>
    <p><b>Lokasi Pengambilan:</b> <?= $main['pickup_location'] ?></p>
    <p><b>Status:</b> <?= ucfirst($main['status']) ?></p>

    <h3>Barang yang Dipinjam</h3>

    <?php while ($i = $items->fetch_assoc()): ?>
        <div class="item-box">
            <img src="../gambar_item/<?= $i['image'] ?>" alt="">
            <div>
                <p><b><?= $i['name'] ?></b></p>
                <p>Jumlah: <?= $i['quantity'] ?></p>
            </div>
        </div>
    <?php endwhile; ?>

    <br>
    <a href="my_borrowings.php" class="btn">Lihat Semua Peminjaman</a>

</div>

</body>
</html>
