<?php
session_start();
require '../config.php';

// hanya admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// proses update status
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE borrowings SET status = 'approved' WHERE id = $id");
    header("Location: admin_manage_borrowings.php?msg=approved");
    exit;
}

if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE borrowings SET status = 'rejected' WHERE id = $id");
    header("Location: admin_manage_borrowings.php?msg=rejected");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // hapus detail dulu
    $conn->query("DELETE FROM borrowing_details WHERE borrowing_id = $id");
    // hapus induk
    $conn->query("DELETE FROM borrowings WHERE id = $id");

    header("Location: admin_manage_borrowings.php?msg=deleted");
    exit;
}

// ambil peminjaman
$sql = "
SELECT b.id, b.user_id, b.status, b.created_at, u.name AS user_name
FROM borrowings b
JOIN users u ON b.user_id = u.id
ORDER BY b.created_at DESC
";

$borrowings = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kelola Peminjaman</title>
<style>
    body { font-family: Arial; background:#f5f5f5; padding:20px; }
    table { width:100%; border-collapse:collapse; background:white; }
    th, td { border:1px solid #ccc; padding:10px; text-align:left; }
    th { background:#eee; }
    a.button { padding:6px 10px; background:#3498db; color:white; text-decoration:none; border-radius:4px; }
    a.reject { background:#e74c3c; }
    a.delete { background:#555; }
    .ok { background:#2ecc71; padding:10px; margin-bottom:10px; color:white; }
</style>
</head>
<body>

<h2>Kelola Peminjaman Barang</h2>

<?php if (isset($_GET['msg'])): ?>
<div class="ok">Aksi berhasil: <?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Peminjam</th>
        <th>Tanggal</th>
        <th>Status</th>
        <th>Detail</th>
        <th>Aksi</th>
    </tr>

    <?php while ($row = $borrowings->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['user_name']) ?></td>
        <td><?= $row['created_at'] ?></td>
        <td><?= $row['status'] ?></td>

        <td>
            <a href="admin_view_borrowing.php?id=<?= $row['id'] ?>" class="button">Lihat</a>
        </td>

        <td>
            <?php if ($row['status'] === 'pending'): ?>
                <a class="button" href="?approve=<?= $row['id'] ?>">Setujui</a>
                <a class="button reject" href="?reject=<?= $row['id'] ?>">Tolak</a>
            <?php endif; ?>

            <a class="button delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus peminjaman ini?')">Hapus</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
