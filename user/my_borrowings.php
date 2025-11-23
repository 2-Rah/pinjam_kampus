<?php
session_start();
require '../config.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* ==========================================================
   HANDLE HAPUS PENGAJUAN (hanya pending)
   ========================================================== */
if (isset($_GET['cancel'])) {
    $cancel_id = intval($_GET['cancel']);

    // Hanya hapus kalau status pending
    $check = $conn->prepare("SELECT status FROM borrowings WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $cancel_id, $user_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if ($res && $res['status'] === 'pending') {
        // delete borrowings → otomatis delete borrowing_details (ON DELETE CASCADE)
        $del = $conn->prepare("DELETE FROM borrowings WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $cancel_id, $user_id);
        $del->execute();

        header("Location: my_borrowings.php?deleted=1");
        exit;
    }
}

/* ==========================================================
   AMBIL SEMUA PEMINJAMAN USER + DETAIL ITEM
   ========================================================== */
$sql = "
    SELECT 
        b.id AS borrowing_id,
        b.description,
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

/* ==========================================================
   UBAH MENJADI STRUKTUR TERKELOMPOK
   ========================================================== */
$borrowings = [];

while ($row = $result->fetch_assoc()) {
    $bid = $row['borrowing_id'];

    if (!isset($borrowings[$bid])) {
        $borrowings[$bid] = [
            "description" => $row['description'],
            "start_date"  => $row['start_date'],
            "end_date"    => $row['end_date'],
            "status"      => $row['status'],
            "items"       => []
        ];
    }

    // masukkan item ke array
    $borrowings[$bid]["items"][] = [
        "name"     => $row['item_name'],
        "quantity" => $row['quantity']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peminjaman Saya</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; padding:20px; }
        .card {
            background:white; padding:15px; margin-bottom:20px;
            border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);
        }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:8px; border:1px solid #ddd; }
        th { background:#fafafa; }
        .delete-btn {
            color:red; text-decoration:none; font-weight:bold;
        }
        .delete-btn:hover { text-decoration:underline; }
        .status-box { padding:4px 8px; border-radius:4px; font-size:12px; }
        .pending { background:#fff3cd; }
        .approved { background:#cfe2ff; }
        .borrowed { background:#d1e7dd; }
        .returned { background:#e2e3e5; }
    </style>
</head>
<body>

<h2>Peminjaman Saya</h2>

<?php if (isset($_GET['deleted'])): ?>
    <p style="color:green;">Peminjaman berhasil dibatalkan.</p>
<?php endif; ?>

<?php if (empty($borrowings)): ?>
    <p>Tidak ada peminjaman.</p>
<?php endif; ?>

<?php foreach ($borrowings as $id => $b): ?>
<div class="card">
    <h3>
        <?= htmlspecialchars($b['description'] ?: "Peminjaman #$id") ?>
    </h3>

    <p>
        <b>ID:</b> <?= $id ?><br>
        <b>Tanggal:</b> <?= $b['start_date'] ?> → <?= $b['end_date'] ?><br>
        <b>Status:</b>
        <span class="status-box <?= $b['status'] ?>">
            <?= $b['status'] ?>
        </span>
    </p>

    <table>
        <tr>
            <th>Nama Barang</th>
            <th>Jumlah</th>
        </tr>

        <?php foreach ($b['items'] as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p style="margin-top:10px;">
        <?php if ($b['status'] === 'pending'): ?>
            <a class="delete-btn" 
               href="my_borrowings.php?cancel=<?= $id ?>"
               onclick="return confirm('Yakin batalkan pengajuan ini?')">
                ❌ Batalkan Pengajuan
            </a>
        <?php else: ?>
            <i>Tidak bisa dibatalkan.</i>
        <?php endif; ?>
    </p>
</div>
<?php endforeach; ?>

</body>
</html>
