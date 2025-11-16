<?php
session_start();
require '../config.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}


$user_id = $_SESSION['user_id'];

// AMBIL DATA PEMINJAMAN + NAMA BARANG
$query = "
    SELECT 
        b.id AS borrowing_id,
        b.start_date,
        b.end_date,
        b.status,
        i.name AS item_name,
        bd.quantity
    FROM borrowings b
    JOIN borrowing_details bd ON bd.borrowing_id = b.id
    JOIN items i ON i.id = bd.item_id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result();

// HANDLE CANCEL
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);

    // hapus di borrowings (akan otomatis hapus borrowing_details karena ON DELETE CASCADE)
    $del = $conn->prepare("DELETE FROM borrowings WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $id, $user_id);
    $del->execute();

    header("Location: my_borrowings.php?deleted=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peminjaman Saya</title>
</head>
<body>

<h2>Daftar Peminjaman Saya</h2>

<?php if (isset($_GET['deleted'])): ?>
    <p style="color:green;">Peminjaman berhasil dihapus.</p>
<?php endif; ?>

<table border="1" cellpadding="8">
    <tr>
        <th>Nama Barang</th>
        <th>Jumlah</th>
        <th>Tgl Mulai</th>
        <th>Tgl Selesai</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php while($row = $data->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['start_date'] ?></td>
            <td><?= $row['end_date'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <?php if ($row['status'] == 'pending'): ?>
                    <a href="?cancel=<?= $row['borrowing_id'] ?>" onclick="return confirm('Hapus peminjaman ini?')">❌ Batalkan</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>
