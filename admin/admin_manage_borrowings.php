<?php
session_start();
require '../config.php';

// hanya admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['borrowing_id']);
    $status = $_POST['status'];

    // validasi status
    $allowed = ['pending','approved','rejected','picked_up'];
    if (!in_array($status, $allowed)) {
        $status = 'pending';
    }

    $stmt = $conn->prepare("UPDATE borrowings SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    header("Location: admin_manage_borrowings.php?msg=status_updated");
    exit;
}

// ambil peminjaman + deskripsi
$sql = "
SELECT b.id, b.user_id, b.status, b.created_at, b.description,
       u.name AS user_name
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
    select { padding:5px; }
    .ok { background:#2ecc71; padding:10px; margin-bottom:10px; color:white; }
    a.button { padding:6px 10px; background:#3498db; color:white; text-decoration:none; border-radius:4px; }
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
        <th>Deskripsi</th>
        <th>Peminjam</th>
        <th>Tanggal</th>
        <th>Status</th>
        <th>Detail</th>
        <th>Simpan</th>
    </tr>

    <?php while ($row = $borrowings->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>

        <!-- Kolom deskripsi -->
        <td><?= htmlspecialchars($row['description']) ?></td>

        <td><?= htmlspecialchars($row['user_name']) ?></td>
        <td><?= $row['created_at'] ?></td>

        <td>
            <!-- dropdown update status -->
            <form method="POST" style="display:flex; gap:5px;">
                <input type="hidden" name="borrowing_id" value="<?= $row['id'] ?>">
                <select name="status">
                    <option value="pending"   <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved"  <?= $row['status']=='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected"  <?= $row['status']=='rejected'?'selected':'' ?>>Rejected</option>
                    <option value="picked_up" <?= $row['status']=='picked_up'?'selected':'' ?>>Picked Up</option>
                </select>
        </td>

        <td>
            <a href="admin_view_borrowing.php?id=<?= $row['id'] ?>" class="button">Lihat</a>
        </td>

        <td>
                <button type="submit" name="update_status" style="padding:6px 12px;">Simpan</button>
            </form>
        </td>

    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
