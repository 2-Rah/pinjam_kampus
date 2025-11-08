<?php
// config.php
session_start(); // opsional, tapi aman jika dipakai di banyak file

$host = 'localhost';
$dbname = 'campus_borrowing';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>