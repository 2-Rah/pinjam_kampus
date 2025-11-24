<?php
session_start();
require '../config.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ==============================
// HANDLE UPDATE STATUS
// ==============================
if (isset($_POST['update_status'])) {
    $return_id = intval($_POST['return_id']);
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? null;
    $admin_id = $_SESSION['admin_id'];

    $stmt = $conn->prepare("
        UPDATE returns 
        SET status = ?, rejection_reason = ?, approved_by = ?, approved_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $status, $reason, $admin_id, $return_id);
    $stmt->execute();

    header("Location: admin_manage_returns.php?view=$return_id&updated=1");
    exit;
}

// ==============================
// AMBIL SEMUA RETURNS
// ==============================
$list = mysqli_query($conn, "
    SELECT 
        r.id, 
        r.borrowing_id, 
        b.description AS borrow_desc,
        r.user_id, 
        r.status, 
        r.created_at, 
        u.name AS username
    FROM returns r
    JOIN users u ON u.id = r.user_id
    JOIN borrowings b ON b.id = r.borrowing_id
    ORDER BY r.id DESC
");

// ==============================
// DETAIL RETURN
// ==============================
$detail = null;
$items = [];

if (isset($_GET['view'])) {
    $return_id = intval($_GET['view']);

    $d = mysqli_query($conn, "
        SELECT 
            r.*, 
            u.name AS username,
            b.description AS borrow_desc
        FROM returns r
        JOIN users u ON u.id = r.user_id
        JOIN borrowings b ON b.id = r.borrowing_id
        WHERE r.id = $return_id
    ");

    $detail = mysqli_fetch_assoc($d);

    // Detail barang
    $items = mysqli_query($conn, "
        SELECT 
            rd.*, 
            i.name AS item_name, 
            bd.quantity AS borrowed_qty
        FROM return_details rd
        JOIN items i ON i.id = rd.item_id
        JOIN borrowing_details bd ON bd.id = rd.borrowing_detail_id
        WHERE rd.return_id = $return_id
    ");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Kelola Pengembalian</title>
<style>
body { font-family:Arial; margin:0; background:#f5f5f5; }
.container { display:flex; height:100vh; }
.left {
    width:35%; background:white; border-right:1px solid #ddd; 
    overflow-y:auto; padding:20px;
}
.right {
    width:65%; padding:20px; overflow-y:auto;
}
.card {
    padding:12px; background:white; border-radius:8px; 
    margin-bottom:12px; border:1px solid #ddd;
}
.card:hover { background:#f0f7ff; cursor:pointer; }
.active { border:2px solid #007bff; }

table { width:100%; border-collapse:collapse; margin-top:10px; background:white; }
th, td { padding:10px; border:1px solid #ccc; }
th { background:#f0f0f0; }

img { max-width:120px; border-radius:6px; }

.status-box {
    padding:6px 10px; border-radius:6px; font-size:12px; color:white;
}
.pending { background:#f0ad4e; }
.approved { background:#0275d8; }
.rejected { background:#d9534f; }
.completed { background:#5cb85c; }

.view-btn {
    padding:6px 10px;
    background:#007bff;
    color:white;
    border:none;
    border-radius:5px;
    text-decoration:none;
    cursor:pointer;
}

/* ======================= */
/*  MODAL FULLSCREEN FOTO  */
/* ======================= */
.modal {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.85);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal img {
    max-width:90vw; 
    max-height:90vh;
    border-radius:10px;
    object-fit:contain;
}

.close-btn {
    position:absolute;
    top:20px;
    right:30px;
    font-size:40px;
    font-weight:bold;
    color:white;
    cursor:pointer;
    user-select:none;
}
</style>
</head>
<body>

<div class="container">

    <!-- ========== LEFT LIST ============= -->
    <div class="left">
        <h2>Daftar Pengembalian</h2>

        <?php while ($r = mysqli_fetch_assoc($list)): ?>
            <a href="admin_manage_returns.php?view=<?= $r['id'] ?>" style="text-decoration:none; color:black;">
                <div class="card <?= (isset($_GET['view']) && $_GET['view']==$r['id'])?'active':'' ?>">
                    <b>Return ID:</b> <?= $r['id'] ?><br>
                    <b>Judul:</b> <?= htmlspecialchars($r['borrow_desc']) ?><br>
                    <b>User:</b> <?= htmlspecialchars($r['username']) ?><br>

                    <span class="status-box <?= $r['status'] ?>">
                        <?= $r['status'] ?>
                    </span><br>

                    <small><?= $r['created_at'] ?></small>
                </div>
            </a>
        <?php endwhile; ?>
    </div>

    <!-- ========== RIGHT DETAILS ============= -->
    <div class="right">

    <?php if (!$detail): ?>

        <h2>Pilih pengembalian di sebelah kiri</h2>

    <?php else: ?>

        <h2>Detail Pengembalian #<?= $detail['id'] ?></h2>

        <?php if (isset($_GET['updated'])): ?>
            <p style="color:green;">Status berhasil diperbarui.</p>
        <?php endif; ?>

        <p>
            <b>Judul:</b> <?= htmlspecialchars($detail['borrow_desc']) ?><br>
            <b>User:</b> <?= htmlspecialchars($detail['username']) ?><br>
            <b>Borrowing ID:</b> <?= $detail['borrowing_id'] ?><br>
            <b>Tanggal Pengajuan:</b> <?= $detail['created_at'] ?><br>

            <b>Status:</b>
            <span class="status-box <?= $detail['status'] ?>"><?= $detail['status'] ?></span><br>

            <?php if (!empty($detail['rejection_reason'])): ?>
                <b>Alasan Penolakan:</b> <?= htmlspecialchars($detail['rejection_reason']) ?><br>
            <?php endif; ?>
        </p>

        <h3>Barang yang Dikembalikan</h3>

        <table>
            <tr>
                <th>Barang</th>
                <th>Dipinjam</th>
                <th>Dikembalikan</th>
                <th>Kondisi</th>
                <th>Foto</th>
            </tr>

            <?php while ($i = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?= htmlspecialchars($i['item_name']) ?></td>
                <td><b><?= $i['borrowed_qty'] ?></b></td>
                <td><?= $i['quantity'] ?></td>
                <td><?= htmlspecialchars($i['item_condition']) ?></td>
                <td>
                    <?php if (!empty($i['image'])): ?>
                        <button class="view-btn" onclick="openModal('../user/<?= htmlspecialchars($i['image']) ?>')">
                            Lihat Foto
                        </button>
                    <?php else: ?>
                        <i>Tidak ada foto</i>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h3>Perbarui Status</h3>

        <form method="POST">
            <input type="hidden" name="return_id" value="<?= $detail['id'] ?>">

            <label>Status:</label><br>
            <select name="status" required>
                <option value="pending" <?= $detail['status']=='pending'?'selected':'' ?>>Pending</option>
                <option value="approved" <?= $detail['status']=='approved'?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= $detail['status']=='rejected'?'selected':'' ?>>Rejected</option>
            </select>

            <br><br>
            <label>Alasan Penolakan (opsional):</label><br>
            <textarea name="reason" rows="3" style="width:100%;"><?= htmlspecialchars($detail['rejection_reason'] ?? '') ?></textarea>

            <br><br>
            <button type="submit" name="update_status" 
                style="padding:10px 20px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer;">
                Simpan Perubahan
            </button>
        </form>

    <?php endif; ?>

    </div>
</div>

<!-- ======================= -->
<!--     MODAL FULLSCREEN    -->
<!-- ======================= -->
<div id="imgModal" class="modal">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <img id="modalImage">
</div>

<script>
function openModal(src) {
    document.getElementById("modalImage").src = src;
    document.getElementById("imgModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("imgModal").style.display = "none";
}
</script>

</body>
</html>
