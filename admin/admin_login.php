<?php
session_start();
require '../db.php'; // pastikan file ini membuat $conn (mysqli)

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim_nip = isset($_POST['nim_nip']) ? trim($_POST['nim_nip']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($nim_nip === '' || $password === '') {
        $error = 'NIM/NIP dan password wajib diisi.';
    } else {
        // Ambil user yang role = admin
        $stmt = $conn->prepare("SELECT id, name, nim_nip, password, role FROM users WHERE nim_nip = ? AND role = 'admin' LIMIT 1");
        $stmt->bind_param("s", $nim_nip);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $password === $user['password']) {
            // login sukses
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'NIM/NIP atau password salah, atau bukan admin.';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Login Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial;background:#f4f4f4;display:flex;align-items:center;justify-content:center;height:100vh}
.card{background:#fff;padding:20px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.1);width:320px}
input{width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:4px}
button{width:100%;padding:10px;border:0;background:#2b8aef;color:#fff;border-radius:4px;cursor:pointer}
.err{color:#a33;margin-bottom:8px}
</style>
</head>
<body>
<div class="card">
    <h3>Login Admin (plain-text password)</h3>

    <?php if ($error): ?>
        <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="nim_nip">NIM / NIP</label>
        <input id="nim_nip" name="nim_nip" type="text" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Masuk</button>
    </form>
</div>
</body>
</html>
