<?php
session_start();
require '../config.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim_nip = isset($_POST['nim_nip']) ? trim($_POST['nim_nip']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($nim_nip === '' || $password === '') {
        $error = 'NIM/NIP dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, nim_nip, password, role FROM users WHERE nim_nip = ? AND role = 'admin' LIMIT 1");
        $stmt->bind_param("s", $nim_nip);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $password_valid = false;
            
            if (substr($user['password'], 0, 4) === '$2y$' || substr($user['password'], 0, 4) === '$2a$') {
                $password_valid = password_verify($password, $user['password']);
                
                if ($password_valid && password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_hash, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            } else {
                $password_valid = ($password === $user['password']);
                
                if ($password_valid) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $new_hash, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }

            if ($password_valid) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_nim_nip'] = $user['nim_nip'];
                $_SESSION['login_time'] = time();
                
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'NIM/NIP tidak ditemukan atau bukan admin.';
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
<title>Login Admin - Sistem Peminjaman Kampus</title>
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

.login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 440px;
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
    animation: shake 0.5s;
}

.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fee2e2;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.form-group {
    margin-bottom: 24px;
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

.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle svg {
    width: 20px;
    height: 20px;
    fill: #94a3b8;
    transition: fill 0.2s;
}

.password-toggle:hover svg {
    fill: #64748b;
}

.btn-login {
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

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-login:active {
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
    .login-container {
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
    <div class="login-container">
        <div class="header">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                </svg>
            </div>
            <h1>Login Admin</h1>
            <p>Masuk untuk mengelola sistem peminjaman</p>
        </div>

        <div class="form-section">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nim_nip">NIM / NIP</label>
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="nim_nip" 
                            name="nim_nip" 
                            required 
                            autofocus
                            placeholder="Masukkan NIM atau NIP"
                            value="<?= isset($_POST['nim_nip']) ? htmlspecialchars($_POST['nim_nip']) : '' ?>"
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
                            placeholder="Masukkan password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg id="eye-icon" viewBox="0 0 24 24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Masuk
                </button>
            </form>

            <div class="divider">
                <span>atau</span>
            </div>

            <div class="links">
                <p>
                    Login sebagai user? <a href="../user/user_login.php">Klik di sini</a>
                </p>
            </div>
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="../index.php">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    Beranda
                </a>
                <span class="separator">•</span>
                <a href="../registrasion.php">
                    <svg viewBox="0 0 24 24">
                        <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Daftar Akun
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
            }
        }
    </script>
</body>
</html>