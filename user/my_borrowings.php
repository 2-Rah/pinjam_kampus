<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua peminjaman user
$stmt = $conn->prepare("SELECT b.*, i.name as item_name, i.type as item_type, i.category
                        FROM borrowings b
                        JOIN items i ON b.item_id = i.id
                        WHERE b.user_id = ?
                        ORDER BY b.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowings = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Saya</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .back-btn { background: #3498db; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .header { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .borrowing-card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .item-name { font-size: 1.2rem; font-weight: bold; color: #2c3e50; }
        .item-category { color: #7f8c8d; font-size: 0.9rem; }
        .card-body { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem; }
        .info-item { }
        .info-label { color: #7f8c8d; font-size: 0.85rem; margin-bottom: 0.3rem; }
        .info-value { font-weight: 500; color: #2c3e50; }
        .status-badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
        .status-menunggu { background: #f39c12; color: white; }
        .status-disetujui { background: #3498db; color: white; }
        .status-ditolak { background: #e74c3c; color: white; }
        .status-sedang_dipinjam { background: #9b59b6; color: white; }
        .status-dikembalikan { background: #2ecc71; color: white; }
        .description-box { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .empty-state { text-align: center; padding: 3rem; color: #7f8c8d; background: white; border-radius: 8px; }
        .timeline { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ecf0f1; }
        .timeline-item { font-size: 0.85rem; color: #7f8c8d; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📋 Riwayat Peminjaman Saya</h1>
        <a href="user_dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
    </nav>

    <div class="container">
        <div class="header">
            <h2>Daftar Peminjaman Anda</h2>
            <p>Lihat status dan riwayat semua peminjaman Anda</p>
        </div>

        <?php if ($borrowings->num_rows > 0): ?>
            <?php while ($row = $borrowings->fetch_assoc()): ?>
                <div class="borrowing-card">
                    <div class="card-header">
                        <div>
                            <div class="item-name"><?= htmlspecialchars($row['item_name']) ?></div>
                            <div class="item-category">
                                <?= $row['item_type'] === 'barang' ? '📦' : '🏢' ?> 
                                <?= htmlspecialchars($row['category']) ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?= $row['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="info-item">
                            <div class="info-label">Tanggal Mulai</div>
                            <div class="info-value"><?= date('d M Y', strtotime($row['start_date'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tanggal Selesai</div>
                            <div class="info-value"><?= date('d M Y', strtotime($row['end_date'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tanggal Pengajuan</div>
                            <div class="info-value"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Durasi</div>
                            <div class="info-value">
                                <?php
                                $start = new DateTime($row['start_date']);
                                $end = new DateTime($row['end_date']);
                                $diff = $start->diff($end);
                                echo $diff->days + 1 . ' hari';
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($row['description']): ?>
                        <div class="description-box">
                            <strong>Keperluan:</strong><br>
                            <?= nl2br(htmlspecialchars($row['description'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($row['rejection_reason']): ?>
                        <div class="description-box" style="background: #fee;">
                            <strong>Alasan Penolakan:</strong><br>
                            <?= nl2br(htmlspecialchars($row['rejection_reason'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="timeline">
                        <div class="timeline-item">📝 Diajukan: <?= date('d M Y H:i', strtotime($row['created_at'])) ?></div>
                        <?php if ($row['approved_at']): ?>
                            <div class="timeline-item">✅ Disetujui: <?= date('d M Y H:i', strtotime($row['approved_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($row['returned_at']): ?>
                            <div class="timeline-item">🔙 Dikembalikan: <?= date('d M Y H:i', strtotime($row['returned_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>Belum ada riwayat peminjaman</h3>
                <p>Mulai ajukan peminjaman barang atau ruangan dari dashboard</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>