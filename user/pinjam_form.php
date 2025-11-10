<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['item_id'])) {
    echo "Tidak ada barang dipilih!";
    exit;
}

$item_ids = $_POST['item_id'];
$ids_str = implode(",", array_map('intval', $item_ids)); // untuk in()
$data = $conn->query("SELECT * FROM items WHERE id IN ($ids_str)");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Konfirmasi Peminjaman</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <h2 class="title">Konfirmasi Peminjaman</h2>

    <form action="pinjam_process.php" method="POST">

        <?php foreach($item_ids as $id): ?>
            <input type="hidden" name="item_id[]" value="<?= $id ?>">
        <?php endforeach ?>

        <table>
            <thead>
            <tr>
                <th>Nama</th>
                <th>Kategori</th>
            </tr>
            </thead>
            <tbody>
            <?php while($b = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($b['name']) ?></td>
                    <td><?= htmlspecialchars($b['category']) ?></td>
                </tr>
            <?php endwhile ?>
            </tbody>
        </table>

        <br>
        Tanggal Pinjam: <input type="date" name="start_date" required><br><br>
        Tanggal Kembali: <input type="date" name="end_date" required><br><br>
        Deskripsi: <br>
        <textarea name="description" required></textarea>
        <br><br>
        <button type="submit" class="btn">Kirim Pengajuan</button>

    </form>
</div>
</body>
</html>
