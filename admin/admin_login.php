<?php
session_start();

// Jika admin sudah login, redirect ke dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim_nip = trim($_POST['nim_nip']);
    $password = $_POST['password'];

    if (empty($nim_nip) || empty($password)) {
        $error = "NIM/NIP dan password wajib diisi.";
    } else {
        require_once '../config.php'; // karena config.php ada di luar folder admin

        // Cari admin berdasarkan nim_nip dan role = 'admin'
        $stmt = mysqli_prepare($conn, 
            "SELECT id, name, password FROM users WHERE nim_nip = ? AND role = 'admin'"
        );
        mysqli_stmt_bind_param($stmt, "s", $nim_nip);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $admin['password'])) {
                // Login sukses
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['user_role'] = 'admin';
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "NIM/NIP tidak ditemukan atau bukan akun admin.";
        }

        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Peminjaman Kampus</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #34495e;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #c0392b;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔒 Login Admin</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="nim_nip">NIM / NIP Admin</label>
                <input type="text" id="nim_nip" name="nim_nip" required autocomplete="off" placeholder="Contoh: 19990101">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login sebagai Admin</button>
        </form>
    </div>
</body>
</html>