<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$borrow_id = $_GET['borrow_id'] ?? null;

if (!$borrow_id) {
    die("Borrowing ID tidak ditemukan!");
}

/* ===========================
   CEK APAKAH RETURN SUDAH ADA
   =========================== */
$check = mysqli_query($conn, "SELECT * FROM returns WHERE borrowing_id=$borrow_id");
$return_row = mysqli_fetch_assoc($check);
$return_id = $return_row['id'] ?? null;

/* ==============================
   HANDLE FORM CONFIRM RETURN
   ============================== */
if (isset($_POST['confirm_return'])) {

    $detail_id = $_POST['detail_id'];
    $item_id = $_POST['item_id'];
    $qty = intval($_POST['qty']);
    $condition = $_POST['condition'];

    if ($qty <= 0) {
        $error = "Quantity tidak boleh nol!";
    }

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
        $error = "Foto wajib diupload!";
    }

    if (!isset($error)) {

        // buat return record kalau belum ada
        if (!$return_id) {
            mysqli_query($conn, "
                INSERT INTO returns (borrowing_id, user_id, return_date)
                VALUES ($borrow_id, $user_id, CURDATE())
            ");
            $return_id = mysqli_insert_id($conn);
        }

        // upload foto
        $folder = "pengembalian_barang/";
        $filename = time() . "_" . basename($_FILES['photo']['name']);
        $path = $folder . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $path);

        // insert return_details
        mysqli_query($conn, "
            INSERT INTO return_details 
            (return_id, item_id, borrowing_detail_id, quantity, item_condition, image)
            VALUES ($return_id, $item_id, $detail_id, $qty, '$condition', '$path')
        ");

        $success = "Pengembalian berhasil disimpan!";
    }
}

/* ==============================
   HANDLE EDIT RETURN DETAIL
   ============================== */
if (isset($_POST['edit_return'])) {

    $rd_id = $_POST['rd_id'];
    $qty = intval($_POST['qty']);
    $condition = $_POST['condition'];

    // Foto baru opsional
    $update_photo = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $folder = "pengembalian_barang/";
        $filename = time() . "_" . basename($_FILES['photo']['name']);
        $path = $folder . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $path);

        $update_photo = ", image='$path'";
    }

    mysqli_query($conn, "
        UPDATE return_details SET 
        quantity=$qty,
        item_condition='$condition'
        $update_photo
        WHERE id=$rd_id
    ");

    $success = "Data pengembalian berhasil diperbarui!";
}

/* ===========================
   AMBIL LIST BARANG DIPINJAM
   =========================== */
$data = mysqli_query($conn, "
    SELECT bd.id AS detail_id, bd.quantity AS borrowed_qty,
           i.id AS item_id, i.name
    FROM borrowing_details bd
    JOIN items i ON i.id = bd.item_id
    WHERE bd.borrowing_id = $borrow_id
");

/* ===========================
   AMBIL RETURN DETAIL JIKA ADA
   =========================== */
$returned_items = [];
if ($return_id) {
    $r = mysqli_query($conn, "
        SELECT * FROM return_details WHERE return_id=$return_id
    ");
    while ($row = mysqli_fetch_assoc($r)) {
        $returned_items[$row['borrowing_detail_id']] = $row;
    }
}
?>

<!-- ==========================
         HTML + CSS
============================== -->

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return Borrowing</title>

<style>
body {
    font-family: Arial;
    background: #f8f8f8;
    padding: 20px;
}
.container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 900px;
    margin: auto;
    box-shadow: 0 0 10px #ccc;
}
table {
    width:100%;
    border-collapse: collapse;
}
table th, table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}
label { font-weight: bold; }
.success { background: #d4edda; padding: 10px; margin-bottom: 10px; border-left: 5px solid #28a745; }
.error { background: #f8d7da; padding: 10px; margin-bottom: 10px; border-left: 5px solid #dc3545; }
button {
    padding: 7px 12px;
    margin-top: 5px;
    cursor: pointer;
}
.confirm-btn {
    background: #007bff;
    color: white;
    border: none;
}
.edit-btn {
    background: #28a745;
    color: white;
    border: none;
}
</style>
</head>

<body>
<div class="container">

<h2>Return Borrowed Items</h2>

<?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
<?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Borrowed Qty</th>
            <th>Return Qty</th>
            <th>Condition</th>
            <th>Photo</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

<?php while ($d = mysqli_fetch_assoc($data)) { 
    $detail_id = $d['detail_id'];
    $returned = $returned_items[$detail_id] ?? null;
?>

<tr>
    <td><?= htmlspecialchars($d['name']) ?></td>
    <td><?= $d['borrowed_qty'] ?></td>

    <?php if (!$returned) { ?>
    <!-- ===========================
         FORM INPUT RETURN BARU
    ============================ -->
    <form method="POST" enctype="multipart/form-data">
        <td>
            <input type="number" name="qty" min="1" max="<?= $d['borrowed_qty'] ?>" required>
        </td>
        <td>
            <select name="condition">
                <option value="good">Good</option>
                <option value="damaged">Damaged</option>
                <option value="lost">Lost</option>
                <option value="needs_repair">Needs Repair</option>
            </select>
        </td>
        <td><input type="file" name="photo" required></td>

        <td>
            <input type="hidden" name="detail_id" value="<?= $detail_id ?>">
            <input type="hidden" name="item_id" value="<?= $d['item_id'] ?>">
            <button class="confirm-btn" type="submit" name="confirm_return">Confirm</button>
        </td>
    </form>

    <?php } else { ?>
    <!-- ===========================
         SUDAH DIKIRIM → TAMPIL EDIT
    ============================ -->
    <form method="POST" enctype="multipart/form-data">
        <td>
            <input type="number" name="qty" min="1" value="<?= $returned['quantity'] ?>">
        </td>

        <td>
            <select name="condition">
                <option value="good" <?= $returned['item_condition']=='good'?'selected':'' ?>>Good</option>
                <option value="damaged" <?= $returned['item_condition']=='damaged'?'selected':'' ?>>Damaged</option>
                <option value="lost" <?= $returned['item_condition']=='lost'?'selected':'' ?>>Lost</option>
                <option value="needs_repair" <?= $returned['item_condition']=='needs_repair'?'selected':'' ?>>Needs Repair</option>
            </select>
        </td>

        <td>
            <input type="file" name="photo">
            <br>
            <small>Existing:</small><br>
            <img src="<?= $returned['image'] ?>" width="70">
        </td>

        <td>
            <input type="hidden" name="rd_id" value="<?= $returned['id'] ?>">
            <button class="edit-btn" name="edit_return">Edit</button>
        </td>
    </form>

    <?php } ?>

</tr>

<?php } ?>
</tbody>
</table>

</div>
</body>
</html>
