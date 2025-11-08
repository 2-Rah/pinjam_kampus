<?php
$servername = "localhost";
$username = "root"; // default Laragon
$password = "";     // kosong di Laragon
$database = "campus_borrowing";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>