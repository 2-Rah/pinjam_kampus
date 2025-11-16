<?php
session_start();
require '../config.php';

if (!isset($_GET['id'])) {
    header("Location: barang_list.php");
    exit;
}

$id = intval($_GET['id']);
$qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1;

// cek barang valid
$q = $conn->query("SELECT * FROM items WHERE id = $id AND is_active = 1");
$barang = $q->fetch_assoc();

if (!$barang) {
    header("Location: barang_list.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// BATASI jumlah agar tidak melebihi stok
if ($qty > $barang['stock']) {
    $qty = $barang['stock'];
}

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity'] += $qty;

    // tetap batasi agar tidak lebih stok
    if ($_SESSION['cart'][$id]['quantity'] > $barang['stock']) {
        $_SESSION['cart'][$id]['quantity'] = $barang['stock'];
    }

} else {
    $_SESSION['cart'][$id] = [
        'id' => $barang['id'],
        'name' => $barang['name'],
        'image' => $barang['image'],
        'stock' => $barang['stock'],
        'quantity' => $qty
    ];
}

header("Location: cart.php?added=1");
exit;
