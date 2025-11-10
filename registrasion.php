<?php
require "db.php";
session_start();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $nim_nip = trim($_POST['nim_nip']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($name === "" || $nim_nip === "" || $email === "" || $password === "") {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Cek apakah NIM/NIP atau email sudah digunakan
        $check = $conn->prepare("SELECT id FROM users WHERE nim_nip = ? OR email = ?");
        $check->bind_param("ss", $nim_nip, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email atau NIM/NIP sudah terdaftar!";
        } else {
            // Simpan user baru dengan role none
            $stmt = $conn->prepare("INSERT INTO users (name, email, nim_nip, password, role) VALUES (?, ?, ?, ?, 'none')");
            $stmt->bind_param("ssss", $name, $email, $nim_nip, $password);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Registrasi User</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial;background:#f4f4f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{background:#fff;padding:20px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.1);width:340px}
input{width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:4px}
button{width:100%;padding:10px;border:0;background:#2b8aef;color:#fff;border-radius:4px;cursor:pointer}
.success{color:#2b8aef;margin-bottom:8px}
.err{color:#a33;margin-bottom:8px}
</style>
</head>
<body>
<div class="card">
    <h3>Registrasi Pengguna Baru</h3>

    <?php if ($error): ?>
        <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Nama Lengkap</label>
        <input type="text" name="name" required>

        <label>NIM / NIP</label>
        <input type="text" name="nim_nip" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Daftar</button>
    </form>

    <p style="text-align:center;margin-top:10px;">
        Sudah punya akun? <a href="user/user_login.php">Login di sini</a>
    </p>
</div>
</body>
</html>
