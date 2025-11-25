<?php
session_start();
require '../config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: user_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim_nip = isset($_POST['nim_nip']) ? trim($_POST['nim_nip']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validasi input kosong
    if ($nim_nip === '' || $password === '') {
        $error = 'NIM/NIP dan password wajib diisi.';
    } else {
        // Ambil user dengan role 'user'
        $stmt = $conn->prepare("
            SELECT id, name, nim_nip, email, password, role 
            FROM users 
            WHERE nim_nip = ? AND role = 'user'
            LIMIT 1
        ");
        $stmt->bind_param("s", $nim_nip);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // FIXED: Cek apakah password menggunakan hash atau plaintext
            $password_valid = false;
            
            // Cek jika password di-hash (dimulai dengan $2y$ atau $2a$)
            if (substr($user['password'], 0, 4) === '$2y$' || substr($user['password'], 0, 4) === '$2a$') {
                // Password sudah di-hash, gunakan password_verify
                $password_valid = password_verify($password, $user['password']);
                
                // Jika valid tapi menggunakan hash lama, rehash dengan algoritma terbaru
                if ($password_valid && password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_hash, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            } else {
                // Password masih plaintext (untuk backward compatibility)
                $password_valid = ($password === $user['password']);
                
                // Jika valid, upgrade ke hash
                if ($password_valid) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_hash, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }

            if ($password_valid) {
                // Login sukses
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nim_nip'] = $user['nim_nip'];
                $_SESSION['login_time'] = time();
                
                // Log aktivitas login (optional)
                $login_log = $conn->prepare("INSERT INTO login_logs (user_id, login_time, ip_address, user_agent) VALUES (?, NOW(), ?, ?)");
                if ($login_log) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $login_log->bind_param("iss", $user['id'], $ip, $ua);
                    $login_log->execute();
                    $login_log->close();
                }
                
                header('Location: user_dashboard.php');
                exit;
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'NIM/NIP tidak ditemukan atau bukan user.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login User - Sistem Peminjaman</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .logo p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .user-badge {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500;
        }
        .form-group input {
            width: 100%; padding: 0.8rem; border: 2px solid #ecf0f1; border-radius: 6px;
            transition: border-color 0.3s; font-size: 1rem;
        }
        .form-group input:focus {
            outline: none; border-color: #56ab2f;
        }
        .error {
            background: #e74c3c; color: white; padding: 0.8rem; border-radius: 6px;
            margin-bottom: 1.5rem; font-size: 0.9rem;
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .btn-login {
            width: 100%; padding: 1rem;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white; border: none; border-radius: 6px; font-weight: bold;
            cursor: pointer; transition: transform 0.2s; font-size: 1rem;
        }
        .btn-login:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(86, 171, 47, 0.4);
        }
        .btn-login:active { transform: translateY(0); }
        .divider { text-align:center; margin:1.5rem 0; color:#7f8c8d; position:relative; }
        .divider::before, .divider::after {
            content:''; position:absolute; top:50%; width:40%; height:1px; background:#ecf0f1;
        }
        .divider::before { left:0; }
        .divider::after { right:0; }
        .links { text-align:center; margin-top:1.5rem; }
        .links a { color:#56ab2f; text-decoration:none; font-weight:500; transition: color 0.3s; }
        .links a:hover { color:#3d7d21; text-decoration: underline; }
        .security-note {
            background: #f8f9fa; padding: 0.8rem; border-radius: 6px;
            margin-top: 1rem; font-size: 0.85rem; color: #666; text-align: center;
        }
        .show-password {
            position: relative;
        }
        .show-password button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #7f8c8d;
            padding: 0;
            width: auto;
        }
        .show-password button:hover {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="login-container">

        <div class="logo">
            <h1>📦 Sistem Peminjaman</h1>
            <div class="user-badge">👤 User Portal</div>
            <p>Pinjam barang kampus dengan mudah</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nim_nip">🆔 NIM / NIP</label>
                <input 
                    type="text" 
                    id="nim_nip" 
                    name="nim_nip" 
                    required 
                    autofocus
                    placeholder="Masukkan NIM/NIP"
                    value="<?= isset($_POST['nim_nip']) ? htmlspecialchars($_POST['nim_nip']) : '' ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">🔒 Password</label>
                <div class="show-password">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Masukkan password"
                    >
                    <button type="button" onclick="togglePassword()" title="Tampilkan password">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                🚀 Masuk ke Dashboard
            </button>
        </form>

        <div class="divider">atau</div>

        <div class="links">
            <p>
                Belum punya akun? <a href="../registrasion.php">📝 Daftar di sini</a>
            </p>
            <p style="margin-top: 0.5rem;">
                <a href="../admin/admin_login.php">🔐 Login sebagai Admin</a>
            </p>
            <p style="margin-top: 0.5rem;">
                <a href="../index.php">⬅️ Kembali ke Beranda</a>
            </p>
        </div>

        <div class="security-note">
            🔒 Koneksi aman · Data terenkripsi
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const button = event.currentTarget;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                button.textContent = '🙈';
                button.title = 'Sembunyikan password';
            } else {
                passwordInput.type = 'password';
                button.textContent = '👁️';
                button.title = 'Tampilkan password';
            }
        }
    </script>
</body>
</html>