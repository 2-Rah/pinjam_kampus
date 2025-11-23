<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int) $_GET['id'];

$q = mysqli_query($conn, "
SELECT rd.*, i.name, bd.quantity AS borrowed_qty
FROM return_details rd
JOIN borrowing_details bd ON bd.id = rd.borrowing_detail_id
JOIN items i ON i.id = rd.item_id
WHERE rd.id = $id
LIMIT 1
");

if (!$q || mysqli_num_rows($q) == 0) {
    die("Data tidak ditemukan.");
}

$data = mysqli_fetch_assoc($q);

/* ================== UPDATE ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)$_POST['quantity'];
    $cond = $_POST['item_condition'];

    if ($qty < 1 || $qty > $data['borrowed_qty']) {
        $error = "Qty tidak valid.";
    } else {
        $updateImage = $data['image'];

        if (!empty($_FILES['image']['name'])) {
            $path = __DIR__ . "/pengembalian_barang/";
            $safe = time() . "_" . basename($_FILES['image']['name']);
            $dest = $path . $safe;

            move_uploaded_file($_FILES['image']['tmp_name'], $dest);
            $updateImage = "pengembalian_barang/" . $safe;
        }

        mysqli_query($conn, "
            UPDATE return_details
            SET quantity=$qty,
                item_condition='$cond',
                image='$updateImage'
            WHERE id=$id
        ");

        header("Location: user_return_borrowing.php?edit_success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Return</title>
<style>
body { font-family:Arial; background:#f5f5f5; padding:25px; }
form { background:white; padding:20px; border-radius:8px; width:300px; }
input, select { width:100%; padding:8px; margin-bottom:10px; }
button { padding:8px 12px; background:#28a745; color:white; border:none; border-radius:5px; }
</style>
</head>
<body>

<h2>Edit Return Item</h2>

<?php if (isset($error)) echo "<div style='color:red'>$error</div>"; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Item</label>
    <input type="text" value="<?= $data['name'] ?>" disabled>

    <label>Qty Returned</label>
    <input type="number" name="quantity" min="1" max="<?= $data['borrowed_qty'] ?>" value="<?= $data['quantity'] ?>">

    <label>Condition</label>
    <select name="item_condition">
        <option value="good" <?= $data['item_condition']=='good'?'selected':'' ?>>Good</option>
        <option value="damaged" <?= $data['item_condition']=='damaged'?'selected':'' ?>>Damaged</option>
        <option value="lost" <?= $data['item_condition']=='lost'?'selected':'' ?>>Lost</option>
        <option value="needs_repair" <?= $data['item_condition']=='needs_repair'?'selected':'' ?>>Needs Repair</option>
    </select>

    <label>Photo (optional)</label>
    <input type="file" name="image">

    <button type="submit">Save</button>
</form>

</body>
</html>
