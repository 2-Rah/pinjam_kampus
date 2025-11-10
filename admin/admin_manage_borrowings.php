<?php
session_start();
require '../db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $new_status = $_POST['status'];
    $rejection_reason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : null;
    
    if ($new_status === 'disetujui') {
        $stmt = $conn->prepare("UPDATE borrowings SET status = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $borrowing_id);
    } elseif ($new_status === 'ditolak') {
        $stmt = $conn->prepare("UPDATE borrowings SET status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $rejection_reason, $borrowing_id);
    } elseif ($new_status === 'dikembalikan') {
        $stmt = $conn->prepare("UPDATE borrowings SET status = ?, returned_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $borrowing_id);
    } else {
        $stmt = $conn->prepare("UPDATE borrowings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $borrowing_id);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Status berhasil diupdate!');</script>";
    }
    $stmt->close();
}

// Ambil semua data peminjaman dengan info user dan item
$query = "SELECT b.*, u.name as user_name, u.nim_nip, i.name as item_name, i.type as item_type, i.category
          FROM borrowings b
          JOIN users u ON b.user_id = u.id
          JOIN items i ON b.item_id = i.id
          ORDER BY 
            CASE b.status
              WHEN 'menunggu' THEN 1
              WHEN 'disetujui' THEN 2
              WHEN 'sedang_dipinjam' THEN 3
              WHEN 'dikembalikan' THEN 4
              WHEN 'ditolak' THEN 5
            END,
            b.created_at DESC";
$borrowings = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; gap: 1rem; }
        .nav-links a { background: #3498db; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        .nav-links a:hover { background: #2980b9; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .header { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: flex; gap: 1rem; margin-top: 1rem; }
        .stat-card { background: #3498db; color: white; padding: 1rem; border-radius: 6px; flex: 1; text-align: center; }
        .stat-card h3 { font-size: 2rem; margin-bottom: 0.5rem; }
        .table-container { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #34495e; color: white; padding: 1rem; text-align: left; font-weight: bold; }
        td { padding: 1rem; border-bottom: 1px solid #ecf0f1; }
        tr:hover { background: #f8f9fa; }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-menunggu { background: #f39c12; color: white; }
        .status-disetujui { background: #3498db; color: white; }
        .status-ditolak { background: #e74c3c; color: white; }
        .status-sedang_dipinjam { background: #9b59b6; color: white; }
        .status-dikembalikan { background: #2ecc71; color: white; }
        .action-form { display: flex; gap: 0.5rem; align-items: center; }
        .action-form select { padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; }
        .action-form button { padding: 0.4rem 0.8rem; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .action-form button:hover { background: #2980b9; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 2rem; width: 500px; border-radius: 8px; }
        .modal-content textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; margin: 1rem 0; }
        .modal-buttons { display: flex; gap: 1rem; justify-content: flex-end; }
        .empty-state { text-align: center; padding: 3rem; color: #7f8c8d; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔧 Kelola Peminjaman</h1>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_manage_items.php">Kelola Items</a>
            <a href="logout_admin.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h2>Manajemen Peminjaman</h2>
            <p>Kelola semua pengajuan peminjaman barang dan ruangan</p>
            <?php
            $stmt = $conn->prepare("SELECT 
                SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'sedang_dipinjam' THEN 1 ELSE 0 END) as dipinjam,
                COUNT(*) as total
                FROM borrowings");
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            ?>
            <div class="stats">
                <div class="stat-card">
                    <h3><?= $stats['menunggu'] ?></h3>
                    <p>Menunggu Persetujuan</p>
                </div>
                <div class="stat-card">
                    <h3><?= $stats['dipinjam'] ?></h3>
                    <p>Sedang Dipinjam</p>
                </div>
                <div class="stat-card">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total Peminjaman</p>
                </div>
            </div>
        </div>

        <div class="table-container">
            <?php if (mysqli_num_rows($borrowings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>NIM/NIP</th>
                            <th>Item</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($borrowings)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['user_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nim_nip']) ?></td>
                                <td><?= htmlspecialchars($row['item_name']) ?><br><small><?= htmlspecialchars($row['category']) ?></small></td>
                                <td><?= $row['item_type'] === 'barang' ? '📦 Barang' : '🏢 Ruangan' ?></td>
                                <td><?= date('d/m/Y', strtotime($row['start_date'])) ?> - <?= date('d/m/Y', strtotime($row['end_date'])) ?></td>
                                <td><span class="status-badge status-<?= $row['status'] ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span></td>
                                <td>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="borrowing_id" value="<?= $row['id'] ?>">
                                        <select name="status" required>
                                            <option value="">Pilih Status</option>
                                            <?php if ($row['status'] === 'menunggu'): ?>
                                                <option value="disetujui">✓ Setujui</option>
                                                <option value="ditolak">✗ Tolak</option>
                                            <?php elseif ($row['status'] === 'disetujui'): ?>
                                                <option value="sedang_dipinjam">→ Sedang Dipinjam</option>
                                                <option value="ditolak">✗ Batalkan</option>
                                            <?php elseif ($row['status'] === 'sedang_dipinjam'): ?>
                                                <option value="dikembalikan">✓ Dikembalikan</option>
                                            <?php endif; ?>
                                        </select>
                                        <button type="submit" name="update_status">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Belum ada data peminjaman</h3>
                    <p>Peminjaman akan muncul di sini setelah user mengajukan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>