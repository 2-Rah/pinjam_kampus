<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* ===========================================================
   Ambil borrowing + details + status return (jika ada)
   =========================================================== */

$sql = "
SELECT 
    b.id AS borrowing_id,
    b.description,
    b.created_at AS borrow_date,
    b.status AS borrow_status,

    r.status AS return_status,
    r.created_at AS return_request_date,

    i.name AS item_name,
    bd.quantity

FROM borrowings b
JOIN borrowing_details bd ON bd.borrowing_id = b.id
JOIN items i ON i.id = bd.item_id
LEFT JOIN returns r ON r.borrowing_id = b.id

WHERE b.user_id = $user_id
  AND b.status IN ('approved','picked_up','borrowed')

ORDER BY b.id DESC
";

$query = mysqli_query($conn, $sql);

/* ===========================================================
   Kelompokkan per borrowing
   =========================================================== */
$borrowings = [];

while ($row = mysqli_fetch_assoc($query)) {

    $id = $row['borrowing_id'];

    if (!isset($borrowings[$id])) {

        // Tentukan status gabungan
        $final_status = "Belum Dikembalikan";

        if ($row['return_status']) {
            // Jika sudah ada pengembalian → pakai status dari returns
            $final_status = $row['return_status'];
        } else {
            // Kalau belum ada returns → status dari borrowing
            if ($row['borrow_status'] == "approved") {
                $final_status = "Menunggu Diambil";
            } elseif ($row['borrow_status'] == "picked_up" || $row['borrow_status'] == "borrowed") {
                $final_status = "Belum Dikembalikan";
            }
        }

        $borrowings[$id] = [
            "description" => $row['description'],
            "borrow_date" => $row['borrow_date'],
            "status" => $final_status,
            "return_request_date" => $row['return_request_date'],
            "items" => []
        ];
    }

    $borrowings[$id]["items"][] = [
        "name" => $row["item_name"],
        "qty" => $row["quantity"]
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Pilih Pengembalian</title>
<style>
body { 
    font-family: Arial; 
    background:#f7f7f7; 
    padding:20px;
}
.card {
    background:white;
    padding:15px;
    margin-bottom:20px;
    border-radius:8px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
table { 
    width:100%; 
    border-collapse:collapse; 
    margin-top:10px;
}
th, td { 
    padding:8px; 
    border:1px solid #ddd; 
}
th { background:#fafafa; }
.btn { 
    padding:7px 12px; 
    background:#007bff; 
    color:white; 
    border:none; 
    border-radius:5px; 
    cursor:pointer;
}
.btn:hover { background:#0056c1; }

.status-box { 
    padding:4px 8px; 
    border-radius:4px; 
    font-size:12px;
    display:inline-block;
}

.pending { background:#fff3cd; }
.approved { background:#cfe2ff; }
.rejected { background:#f8d7da; }
.completed { background:#d1e7dd; }
.default-status { background:#e2e3e5; }

</style>
</head>
<body>

<h2>Pilih Peminjaman untuk Dikembalikan</h2>

<?php if (empty($borrowings)): ?>
    <p>Tidak ada peminjaman aktif.</p>
<?php endif; ?>

<?php foreach ($borrowings as $id => $b): ?>
<div class="card">

    <h3><?= htmlspecialchars($b['description'] ?: "Peminjaman #$id") ?></h3>

    <p>
        <b>ID Peminjaman:</b> <?= $id ?><br>
        <b>Tanggal Pinjam:</b> <?= $b['borrow_date'] ?><br>

        <b>Status Pengembalian:</b>
        <span class="status-box 
            <?= $b['status'] == 'pending' ? 'pending' : '' ?>
            <?= $b['status'] == 'approved' ? 'approved' : '' ?>
            <?= $b['status'] == 'rejected' ? 'rejected' : '' ?>
            <?= $b['status'] == 'completed' ? 'completed' : '' ?>
            <?= $b['status'] == 'Belum Dikembalikan' ? 'default-status' : '' ?>
        ">
            <?= $b['status'] ?>
        </span>

        <?php if ($b['return_request_date']): ?>
            <br><b>Tanggal Pengajuan:</b> <?= $b['return_request_date'] ?>
        <?php endif; ?>
    </p>

    <table>
        <tr>
            <th>Nama Barang</th>
            <th>Jumlah</th>
        </tr>

        <?php foreach ($b['items'] as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $item['qty'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if ($b['status'] != 'completed'): ?>
    <form action="user_return_borrowing.php" method="GET" style="margin-top:12px;">
        <input type="hidden" name="borrow_id" value="<?= $id ?>">
        <button class="btn">Kembalikan</button>
    </form>
    <?php endif; ?>

</div>
<?php endforeach; ?>

</body>
</html>
