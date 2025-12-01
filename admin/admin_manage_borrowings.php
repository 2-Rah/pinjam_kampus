<?php
session_start();
require '../config.php';

// hanya admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['borrowing_id']);
    $status = $_POST['status'];

    $allowed = ['pending','approved','rejected','picked_up'];
    if (!in_array($status, $allowed)) {
        $status = 'pending';
    }

    $stmt = $conn->prepare("UPDATE borrowings SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    header("Location: admin_manage_borrowings.php?msg=status_updated");
    exit;
}

// ambil data
$sql = "
SELECT b.id, b.user_id, b.status, b.created_at, b.description,
       u.name AS user_name
FROM borrowings b
JOIN users u ON b.user_id = u.id
ORDER BY b.created_at DESC
";
$borrowings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Peminjaman - Admin Dashboard</title>
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
    background: #f8fafc;
    color: #1e293b;
    line-height: 1.6;
}

/* NAVBAR */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-brand {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.navbar-links {
    display: flex;
    gap: 24px;
    align-items: center;
}

.navbar-links a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-links a:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.navbar-links a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 16px;
    right: 16px;
    height: 2px;
    background: white;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.navbar-links a:hover::after {
    transform: scaleX(1);
}

/* CONTAINER */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px;
}

/* HEADER SECTION */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.5px;
}

/* SUCCESS MESSAGE */
.alert-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    animation: slideDown 0.5s ease-out;
}

.alert-success svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
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

/* TABLE STYLING */
.table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 32px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475569;
    padding: 16px 20px;
    font-weight: 600;
    font-size: 14px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

tr:last-child td {
    border-bottom: none;
}

tr:hover {
    background: #f8fafc;
}

/* STATUS BADGES */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-approved {
    background: #d1fae5;
    color: #059669;
}

.status-rejected {
    background: #fee2e2;
    color: #dc2626;
}

.status-picked_up {
    background: #dbeafe;
    color: #2563eb;
}

/* FORM ELEMENTS */
.form-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.select-wrapper {
    position: relative;
    min-width: 140px;
}

.select-wrapper select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
}

.select-wrapper::after {
    content: '';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    border-right: 2px solid #64748b;
    border-bottom: 2px solid #64748b;
    transform: translateY(-60%) rotate(45deg);
    pointer-events: none;
}

.select-wrapper select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* BUTTONS */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* ACTION CELLS */
.action-cell {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-state svg {
    width: 64px;
    height: 64px;
    fill: #cbd5e1;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #475569;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 20px;
        flex-direction: column;
        gap: 12px;
    }

    .navbar-links {
        gap: 12px;
    }

    .container {
        padding: 20px;
    }

    .header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 800px;
    }

    .action-cell {
        flex-direction: column;
        gap: 8px;
    }

    .form-group {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="navbar-brand">Admin Sistem Peminjaman</div>
    <div class="navbar-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_manage_role.php">User</a>
        <a href="admin_manage_items.php">Barang</a>
        <a href="admin_manage_borrowings.php">Peminjaman</a>
        <a href="logout_admin.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>Kelola Peminjaman Barang</h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert-success">
        <svg viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        <span>Status peminjaman berhasil diperbarui!</span>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Deskripsi</th>
                    <th>Peminjam</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Detail</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($borrowings->num_rows > 0): ?>
                    <?php while ($row = $borrowings->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $row['status'] ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_view_borrowing.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                Lihat
                            </a>
                        </td>
                        <td>
                            <form method="POST" class="form-group">
                                <input type="hidden" name="borrowing_id" value="<?= $row['id'] ?>">
                                <div class="select-wrapper">
                                    <select name="status">
                                        <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                                        <option value="approved" <?= $row['status']=='approved'?'selected':'' ?>>Approved</option>
                                        <option value="rejected" <?= $row['status']=='rejected'?'selected':'' ?>>Rejected</option>
                                        <option value="picked_up" <?= $row['status']=='picked_up'?'selected':'' ?>>Picked Up</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-success btn-sm">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                    Simpan
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 11.5h.25V19h-4.5v-4.5H10c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1H7c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1h.25V19h-4.5v-4.5H5c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1h.25V19H1V5h22v14h-3.75v-4.5H19c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1h-2c-.55 0-1 .45-1 1v4.5c0 .55.45 1 1 1z"/>
                                </svg>
                                <h3>Tidak ada data peminjaman</h3>
                                <p>Belum ada permintaan peminjaman yang diajukan.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Add smooth animations
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        row.style.animation = 'slideIn 0.5s ease-out forwards';
    });
});

// Add CSS for row animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    tbody tr {
        opacity: 0;
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>