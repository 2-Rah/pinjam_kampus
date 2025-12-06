<?php
require "config.php";
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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE nim_nip = ? OR email = ?");
        $check->bind_param("ss", $nim_nip, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email atau NIM/NIP sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, nim_nip, password, role) VALUES (?, ?, ?, ?, 'none')");
            $stmt->bind_param("ssss", $name, $email, $nim_nip, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Registrasi anda sedang diperiksa oleh admin.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi - Sistem Peminjaman Kampus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

body::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -250px;
    right: -250px;
    animation: float 20s infinite;
}

body::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
    bottom: -200px;
    left: -200px;
    animation: float 15s infinite reverse;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(10deg); }
}

.registration-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 480px;
    width: 100%;
    overflow: hidden;
    position: relative;
    z-index: 1;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header {
    padding: 40px 40px 30px;
    text-align: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid #e9ecef;
}

.logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.logo svg {
    width: 36px;
    height: 36px;
    fill: white;
}

.header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.header p {
    font-size: 14px;
    color: #64748b;
    font-weight: 400;
}

.form-section {
    padding: 40px;
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease-out;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fee2e2;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #1a202c;
    font-weight: 500;
    font-size: 14px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    pointer-events: none;
}

.input-icon svg {
    width: 100%;
    height: 100%;
    fill: #94a3b8;
}

.form-group input {
    width: 100%;
    padding: 12px 14px 12px 44px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input::placeholder {
    color: #cbd5e0;
}

.btn-register {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-register:active {
    transform: translateY(0);
}

.divider {
    display: flex;
    align-items: center;
    margin: 24px 0;
    color: #94a3b8;
    font-size: 13px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}

.divider span {
    padding: 0 16px;
}

.links {
    text-align: center;
}

.links a {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}

.links a:hover {
    color: #5568d3;
    text-decoration: underline;
}

.links p {
    margin-bottom: 12px;
}

.footer {
    padding: 20px 40px;
    text-align: center;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 8px;
    align-items: center;
}

.footer-links a {
    color: #64748b;
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.2s;
}

.footer-links a:hover {
    background: #e2e8f0;
    color: #1a202c;
}

.footer-links svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.footer-links .separator {
    color: #cbd5e0;
}

@media (max-width: 480px) {
    .registration-container {
        border-radius: 16px;
    }

    .header {
        padding: 30px 24px 24px;
    }

    .form-section {
        padding: 30px 24px;
    }

    .footer {
        padding: 16px 24px;
    }

    .footer-links {
        flex-direction: column;
        gap: 0;
    }

    .footer-links .separator {
        display: none;
    }
}
</style>
</head>
<body>
    <div class="registration-container">
        <div class="header">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <h1>Daftar Akun Baru</h1>
            <p>Buat akun untuk mulai menggunakan sistem</p>
        </div>

        <div class="form-section">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required
                            placeholder="Masukkan nama lengkap"
                            value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="nim_nip">NIM / NIP</label>
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="nim_nip" 
                            name="nim_nip" 
                            required
                            placeholder="Masukkan NIM atau NIP"
                            value="<?= isset($_POST['nim_nip']) ? htmlspecialchars($_POST['nim_nip']) : '' ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            placeholder="Masukkan alamat email"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                        >
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    Daftar Sekarang
                </button>
            </form>

            <div class="divider">
                <span>atau</span>
            </div>

            <div class="links">
                <p>
                    Sudah punya akun? <a href="user/user_login.php">Login di sini</a>
                </p>
            </div>
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="index.php">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    Beranda
                </a>
                <span class="separator">•</span>
                <a href="admin/admin_login.php">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                    </svg>
                    Login Admin
                </a>
            </div>
        </div>
    </div>
</body>
</html>