<?php
session_start();
require '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

/* =============================
   FUNGSI HITUNG STATUS
   ============================= */

// 1. Sedang diajukan
$sedang_diajukan = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'pending'
")->fetch_assoc()['c'];

// 2. Pengajuan ditolak
$pengajuan_ditolak = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'rejected'
")->fetch_assoc()['c'];

// 3. Belum diambil
$belum_diambil = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings 
    WHERE user_id = $user_id AND status = 'approved'
")->fetch_assoc()['c'];

// 4. Belum dikembalikan (picked_up namun belum ada pengajuan return)
$belum_dikembalikan = $conn->query("
    SELECT COUNT(*) AS c 
    FROM borrowings b
    WHERE b.user_id = $user_id
      AND b.status = 'picked_up'
      AND NOT EXISTS (SELECT 1 FROM returns r WHERE r.borrowing_id = b.id)
")->fetch_assoc()['c'];

// 5. Pengembalian sedang diperiksa
$pengembalian_diperiksa = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'pending'
")->fetch_assoc()['c'];

// 6. Pengembalian ditolak
$pengembalian_ditolak = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'rejected'
")->fetch_assoc()['c'];

// 7. Peminjaman selesai
$peminjaman_selesai = $conn->query("
    SELECT COUNT(*) AS c
    FROM returns r
    JOIN borrowings b ON r.borrowing_id = b.id
    WHERE b.user_id = $user_id
      AND r.status = 'approved'
")->fetch_assoc()['c'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard User - Sistem Peminjaman</title>
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
    padding: 20px;
    display: flex;
    flex-direction: column;
    color: #1a202c;
}

/* NAVBAR */
.navbar {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(15px);
    padding: 16px 32px;
    border-radius: 16px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.navbar-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
}

.navbar-title svg {
    width: 28px;
    height: 28px;
    fill: white;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 15px;
    font-weight: 500;
}

.logout-btn {
    padding: 10px 20px;
    background: #ff5b5b;
    color: white;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: .3s;
    box-shadow: 0 4px 10px rgba(255,91,91,0.4);
}

.logout-btn:hover {
    background: #e04444;
    transform: translateY(-2px);
}

/* CONTAINER */
.container {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

/* WELCOME SECTION */
.welcome {
    background: white;
    padding: 32px;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    text-align: center;
    margin-bottom: 35px;
    animation: fadeIn 0.6s ease-out;
}

.welcome h2 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #1a202c;
}

.welcome p {
    color: #64748b;
    font-size: 16px;
}

/* STATUS CARDS */
.status-section {
    margin-bottom: 35px;
}

.section-title {
    color: white;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-card {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(15px);
    padding: 24px;
    border-radius: 16px;
    color: white;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

.status-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-label {
    font-size: 14px;
    font-weight: 500;
    opacity: 0.9;
}

/* MENU CARDS */
.menu-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    transition: 0.3s ease;
    animation: fadeIn 0.6s ease-out;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.card-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 16px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 10px 20px rgba(102,126,234,0.3);
}

.card-icon svg {
    width: 34px;
    height: 34px;
    fill: white;
}

.card h3 {
    font-size: 18px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 8px;
    color: #1a202c;
}

.card p {
    text-align: center;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

/* ANIMATIONS */
@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 20px;
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .container {
        padding: 0 10px;
    }

    .welcome {
        padding: 24px;
        margin-bottom: 25px;
    }

    .status-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .status-card {
        padding: 20px;
    }

    .status-number {
        font-size: 2rem;
    }

    .menu-cards {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .card {
        padding: 24px;
    }
}

@media (max-width: 480px) {
    .status-grid {
        grid-template-columns: 1fr;
    }
    
    .status-card {
        padding: 16px;
    }
    
    .status-number {
        font-size: 1.8rem;
    }
}

/* STAGGERED ANIMATION FOR STATUS CARDS */
.status-card:nth-child(1) { animation-delay: 0.1s; }
.status-card:nth-child(2) { animation-delay: 0.2s; }
.status-card:nth-child(3) { animation-delay: 0.3s; }
.status-card:nth-child(4) { animation-delay: 0.4s; }
.status-card:nth-child(5) { animation-delay: 0.5s; }
.status-card:nth-child(6) { animation-delay: 0.6s; }
.status-card:nth-child(7) { animation-delay: 0.7s; }
</style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-title">
            <svg viewBox="0 0 24 24">
                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>
            </svg>
            <span>Dashboard User</span>
        </div>

        <div class="user-info">
            Halo, <strong><?= htmlspecialchars($user_name) ?></strong>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container">

        <!-- WELCOME SECTION -->
        <div class="welcome">
            <h2>Selamat Datang, <?= htmlspecialchars($user_name) ?>!</h2>
            <p>Kelola peminjaman barang dan ruangan kampus dengan mudah</p>
        </div>

        <!-- STATUS SECTION -->
        <div class="status-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                Status Peminjaman Anda
            </h3>

            <div class="status-grid">
                <div class="status-card">
                    <div class="status-number"><?= $sedang_diajukan ?></div>
                    <div class="status-label">Sedang Diajukan</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $pengajuan_ditolak ?></div>
                    <div class="status-label">Pengajuan Ditolak</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $belum_diambil ?></div>
                    <div class="status-label">Belum Diambil</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $belum_dikembalikan ?></div>
                    <div class="status-label">Belum Dikembalikan</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $pengembalian_diperiksa ?></div>
                    <div class="status-label">Pengembalian Diperiksa</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $pengembalian_ditolak ?></div>
                    <div class="status-label">Pengembalian Ditolak</div>
                </div>

                <div class="status-card">
                    <div class="status-number"><?= $peminjaman_selesai ?></div>
                    <div class="status-label">Peminjaman Selesai</div>
                </div>
            </div>
        </div>

        <!-- MENU SECTION -->
        <div class="menu-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24">
                    <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0 4h2v-2H3v2zm12 0h2v-2h-2v2zm0 0h2v-2h-2v2zM3 9h2V7H3v2zm12-4h2V3h-2v2zm4 0h2V3h-2v2zm0 16h2v-2h-2v2zM3 21h2v-2H3v2zm4-12h2V9H7v2zm4-4h2V5h-2v2zm4 0h2V5h-2v2zm4 4h2V9h-2v2zm0 4h2v-2h-2v2zm0 4h2v-2h-2v2zm0 4h2v-2h-2v2z"/>
                </svg>
                Menu Utama
            </h3>

            <div class="menu-cards">
                <a href="barang_list.php" class="card">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                    </div>
                    <h3>Ajukan Peminjaman</h3>
                    <p>Pilih barang atau ruangan yang ingin kamu pinjam</p>
                </a>

                <a href="my_borrowings.php" class="card">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"/>
                        </svg>
                    </div>
                    <h3>Peminjaman Saya</h3>
                    <p>Lihat riwayat dan status peminjaman Anda</p>
                </a>

                <a href="user_return_selection.php" class="card">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.69 2.8l1.46 1.46C19.54 16.02 20 14.57 20 13c0-4.42-3.58-8-8-8zm-6.31 3.2L4.23 6.74C3.46 8 3 9.43 3 11c0 4.42 3.58 8 8 8v4l5-5-5-5v4c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.69-2.8z"/>
                        </svg>
                    </div>
                    <h3>Pengembalian Barang</h3>
                    <p>Kembalikan barang yang sedang kamu pinjam</p>
                </a>
            </div>
        </div>
    </div>

    <script>
    // Add staggered animation for status cards
    document.addEventListener('DOMContentLoaded', function() {
        const statusCards = document.querySelectorAll('.status-card');
        const menuCards = document.querySelectorAll('.card');
        
        statusCards.forEach((card, index) => {
            card.style.animation = `fadeIn 0.6s ease-out ${index * 0.1}s both`;
        });
        
        menuCards.forEach((card, index) => {
            card.style.animation = `fadeIn 0.6s ease-out ${index * 0.1 + 0.3}s both`;
        });
    });
    </script>

</body>
</html>