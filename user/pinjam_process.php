<?php
require "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id     = $_SESSION['user_id'];
$item_ids    = $_POST['item_id'];
$start       = $_POST['start_date'];
$end         = $_POST['end_date'];
$desc        = $_POST['description'];

foreach($item_ids as $item_id) {
    $stmt = $conn->prepare("INSERT INTO borrowings (user_id, item_id, start_date, end_date, description) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iisss", $user_id, $item_id, $start, $end, $desc);
    $stmt->execute();
}

header("Location: barang_list.php?success=1");
exit;
