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

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        b.*, u.name AS user_name, u.nim_nip, u.email,
        admin.name AS admin_name, admin.nim_nip AS admin_nip
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN users admin ON b.approved_by = admin.id
    WHERE b.id = ? AND b.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$borrowing = $stmt->get_result()->fetch_assoc();

$items_sql = "
    SELECT i.name, i.type, bd.quantity
    FROM borrowing_details bd
    JOIN items i ON bd.item_id = i.id
    WHERE bd.borrowing_id = ?
";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $id);
$items_stmt->execute();
$items = $items_stmt->get_result();

$start_date = date('d F Y', strtotime($borrowing['start_date']));
$end_date   = date('d F Y', strtotime($borrowing['end_date']));
$approved_date = $borrowing['approved_at'] ? date('d F Y', strtotime($borrowing['approved_at'])) : '';
$current_date = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Peminjaman</title>
<style>
    body {
        font-family: "Times New Roman", serif;
        margin: 0 auto;
        width: 210mm;
        padding: 20mm;
        background: #fff;
        font-size: 14px;
    }
    .header { text-align: center; margin-bottom: 10px; }
    hr { border: 1px solid #000; }
    table.info td { padding: 4px 0; }
    table.item-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.item-table th, table.item-table td {
        border: 1px solid #000; padding: 6px; text-align: center;
    }
    .signature-section { width: 100%; margin-top: 35px; text-align: center; }
    .print-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        background: linear-gradient(to right, #1e90ff, #8a2be2);
        color: white;
        margin-bottom: 20px;
    }
    @media print { 
        .print-btn { display: none; } 
        body { width: 100%; margin: 0; padding: 0; } 
    }
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print / Save PDF</button>

<div class="header">
    <h3>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
    <h3>UNIVERSITAS UDAYANA</h3>
    <h3>FAKULTAS TEKNIK</h3>
    <p>Jalan Kampus Unud Jimbaran Badung-Bali</p>
    <p>Telepon (0361) 703320, Email: ft@unud.ac.id</p>
</div>

<hr>

<h2 style="text-align:center;">SURAT BUKTI PEMINJAMAN BARANG/ASET</h2>
<p style="text-align:center;">
    Nomor: <?= str_pad($borrowing['id'], 6, "0", STR_PAD_LEFT) ?>/SBP/FT/UNUD/<?= date('Y'); ?>
</p>

<h3>Informasi Peminjaman</h3>
<table class="info">
    <tr><td width="180">Nama Peminjam</td><td>: <b><?= $borrowing['user_name'] ?></b></td></tr>
    <tr><td>NIM</td><td>: <b><?= $borrowing['nim_nip'] ?></b></td></tr>
    <tr><td>Email</td><td>: <b><?= $borrowing['email'] ?></b></td></tr>
    <tr><td>Tanggal Mulai</td><td>: <b><?= $start_date ?></b></td></tr>
    <tr><td>Tanggal Selesai</td><td>: <b><?= $end_date ?></b></td></tr>
    <tr><td>Lokasi Pengambilan</td><td>: <b><?= $borrowing['pickup_location'] ?></b></td></tr>

    <?php if ($borrowing['admin_name']) { ?>
        <tr><td>Disetujui Oleh</td><td>: <b><?= $borrowing['admin_name'] ?></b></td></tr>
        <tr><td>NIP Penyetuju</td><td>: <b><?= $borrowing['admin_nip'] ?></b></td></tr>
    <?php } ?>

    <?php if ($approved_date) { ?>
        <tr><td>Tanggal Persetujuan</td><td>: <b><?= $approved_date ?></b></td></tr>
    <?php } ?>
</table>

<p style="text-align:justify; margin-top:15px;">
    Barang/aset ini digunakan untuk mendukung pelaksanaan kegiatan <b><?= $borrowing['title'] ?></b> 
    dan kebutuhan perlengkapannya selama kegiatan berlangsung.
</p>

<h3>Daftar Barang yang Dipinjam</h3>

<table class="item-table">
    <tr>
        <th>No</th>
        <th>Nama Barang</th>
        <th>Jenis</th>
        <th>Jumlah</th>
    </tr>

    <?php $no = 1; while ($row = $items->fetch_assoc()) { ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['type'] ?></td>
            <td><?= $row['quantity'] ?></td>
        </tr>
    <?php } ?>
</table>

<h3>Pernyataan Tanggung Jawab</h3>
<p style="text-align:justify;">
    Saya yang bertanda tangan di bawah ini, menyatakan bahwa saya bertanggung jawab penuh atas barang/aset 
    yang dipinjam dan akan mengembalikannya dalam keadaan baik sesuai dengan tanggal yang telah ditentukan. 
    Apabila terjadi kerusakan atau kehilangan, saya bersedia mengganti sesuai ketentuan berlaku.
</p>

<div class="signature-section">
    <table width="100%">
        <tr>
            <td>Peminjam<br><br><br><br>
                <b><?= $borrowing['user_name'] ?></b><br>
                NIM/NIP: <?= $borrowing['nim_nip'] ?>
            </td>

            <td>Jimbaran, <?= $current_date ?><br>
                Yang Menyetujui<br><br><br>
                <b><?= $borrowing['admin_name'] ?? "Admin" ?></b><br>
                NIP: <?= $borrowing['admin_nip'] ?>
            </td>
        </tr>
    </table>
</div>

<center><small>Dicetak otomatis dari Sistem Peminjaman Barang</small></center>

</body>
</html>
