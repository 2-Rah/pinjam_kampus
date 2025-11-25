<?php
require '../config.php';
session_start();

// hanya admin yg boleh buka
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$success_message = '';
$error_message = '';

// ===========================
// HANDLE RESET PASSWORD
// ===========================
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['id']);
    
    // Ambil NIM/NIP user
    $user_query = $conn->prepare("SELECT nim_nip, name FROM users WHERE id = ?");
    $user_query->bind_param("i", $id);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    if ($user_data) {
        // Set password = NIM/NIP (di-hash)
        $new_password = password_hash($user_data['nim_nip'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $id);
        
        if ($stmt->execute()) {
            $success_message = "Password untuk <strong>{$user_data['name']}</strong> berhasil direset ke NIM/NIP: <strong>{$user_data['nim_nip']}</strong>";
        } else {
            $error_message = "Gagal reset password: " . $stmt->error;
        }
        $stmt->close();
    }
    $user_query->close();
}

// ===========================
// HANDLE UPDATE ROLE
// ===========================
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $role = $_POST['role'];
    
    // Validasi role
    $allowed_roles = ['none', 'user', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $error_message = "Role tidak valid!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $id);
        
        if ($stmt->execute()) {
            $success_message = "Role berhasil diupdate!";
        } else {
            $error_message = "Gagal update role: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ===========================
// HANDLE DELETE USER
// ===========================
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    
    // Cek apakah user punya borrowing aktif
    $check = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status IN ('pending', 'approved', 'picked_up')");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error_message = "Tidak dapat menghapus user yang masih memiliki peminjaman aktif!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "User berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus user: " . $stmt->error;
        }
        $stmt->close();
    }
    $check->close();
}

// ===========================
// SEARCH & FILTER
// ===========================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

$sql = "SELECT * FROM users WHERE 1=1";

if ($search !== '') {
    $search_param = "%$search%";
    $sql .= " AND (name LIKE ? OR nim_nip LIKE ? OR email LIKE ?)";
}

if ($filter_role !== '' && $filter_role !== 'all') {
    $sql .= " AND role = ?";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

// Bind parameters based on conditions
if ($search !== '' && $filter_role !== '' && $filter_role !== 'all') {
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $filter_role);
} elseif ($search !== '') {
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} elseif ($filter_role !== '' && $filter_role !== 'all') {
    $stmt->bind_param("s", $filter_role);
}

$stmt->execute();
$data = $stmt->get_result();
$total_users = $data->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Role User</title>
<style>
    body { 
        font-family: 'Segoe UI', Tahoma; 
        background: #f5f5f5; 
        margin: 0; 
        padding: 0; 
    }
    
    .navbar { 
        background: #2c3e50; 
        padding: 1rem 2rem; 
        color: white; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .navbar h1 { 
        font-size: 1.5rem; 
        margin: 0; 
    }
    
    .navbar a { 
        color: white; 
        text-decoration: none; 
        margin-left: 1rem;
        padding: 0.5rem 1rem;
        background: #34495e;
        border-radius: 4px;
        transition: background 0.3s;
    }
    
    .navbar a:hover { 
        background: #415b76; 
    }
    
    .container { 
        max-width: 1200px; 
        margin: 2rem auto; 
        background: white; 
        padding: 2rem; 
        border-radius: 8px; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
    }
    
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .header-section h2 {
        margin: 0;
        color: #2c3e50;
    }
    
    .user-count {
        background: #3498db;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
    }
    
    /* Search & Filter */
    .search-filter {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: end;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3498db;
    }
    
    .btn-search {
        background: #3498db;
        color: white;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }
    
    .btn-search:hover {
        background: #2980b9;
    }
    
    .btn-reset-filter {
        background: #95a5a6;
        color: white;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }
    
    .btn-reset-filter:hover {
        background: #7f8c8d;
    }
    
    /* Alert Messages */
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        animation: slideDown 0.3s ease-out;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
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
    
    /* Table Styles */
    .table-wrapper {
        overflow-x: auto;
    }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 1rem; 
    }
    
    th, td { 
        border-bottom: 1px solid #ddd; 
        padding: 12px; 
        text-align: left; 
    }
    
    th { 
        background: #34495e; 
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
    }
    
    tr:hover {
        background: #f8f9fa;
    }
    
    select { 
        padding: 6px 10px; 
        border: 2px solid #ddd;
        border-radius: 4px;
        min-width: 100px;
    }
    
    .role-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .role-admin {
        background: #e74c3c;
        color: white;
    }
    
    .role-user {
        background: #3498db;
        color: white;
    }
    
    .role-none {
        background: #95a5a6;
        color: white;
    }
    
    /* Buttons */
    .btn-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    button { 
        background: #3498db; 
        border: none; 
        padding: 8px 15px; 
        border-radius: 4px; 
        color: white; 
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    button:hover { 
        background: #2980b9;
        transform: translateY(-2px);
    }
    
    .del-btn { 
        background: #e74c3c; 
    }
    
    .del-btn:hover { 
        background: #c0392b; 
    }
    
    .reset-btn {
        background: #f39c12;
    }
    
    .reset-btn:hover {
        background: #d68910;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #7f8c8d;
    }
    
    .empty-state svg {
        width: 100px;
        height: 100px;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
</style>
</head>
<body>

<nav class="navbar">
    <h1>👥 Kelola Role User</h1>
    <div>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="logout_admin.php">🚪 Logout</a>
    </div>
</nav>

<div class="container">
    
    <div class="header-section">
        <h2>Manajemen User & Role</h2>
        <div class="user-count">
            📊 Total: <?= $total_users ?> user
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            ✅ <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            ❌ <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <!-- Search & Filter -->
    <form method="GET" action="" class="search-filter">
        <div class="form-group">
            <label for="search">🔍 Cari User</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                placeholder="Nama, NIM/NIP, atau Email..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="role">🎭 Filter Role</label>
            <select id="role" name="role">
                <option value="all" <?= $filter_role === 'all' || $filter_role === '' ? 'selected' : '' ?>>Semua Role</option>
                <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= $filter_role === 'user' ? 'selected' : '' ?>>User</option>
                <option value="none" <?= $filter_role === 'none' ? 'selected' : '' ?>>None</option>
            </select>
        </div>
        
        <button type="submit" class="btn-search">🔎 Cari</button>
        <a href="admin_manage_role.php" class="btn-reset-filter">🔄 Reset</a>
    </form>
    
    <!-- Table -->
    <div class="table-wrapper">
        <?php if ($total_users > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>NIM/NIP</th>
                    <th>Email</th>
                    <th>Role Saat Ini</th>
                    <th>Ubah Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($u = $data->fetch_assoc()): ?>
            <tr>
                <td><strong>#<?= $u['id'] ?></strong></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><code><?= htmlspecialchars($u['nim_nip']) ?></code></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="role-badge role-<?= $u['role'] ?>">
                        <?= strtoupper($u['role']) ?>
                    </span>
                </td>
                <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirmUpdate('<?= htmlspecialchars($u['name']) ?>', this.role.value)">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <select name="role">
                            <option value="none" <?= $u['role'] == "none" ? "selected" : ""; ?>>None</option>
                            <option value="user" <?= $u['role'] == "user" ? "selected" : ""; ?>>User</option>
                            <option value="admin" <?= $u['role'] == "admin" ? "selected" : ""; ?>>Admin</option>
                        </select>
                        <button type="submit" name="update">💾 Update</button>
                    </form>
                </td>
                <td>
                    <div class="btn-group">
                        <form method="POST" style="display: inline;" onsubmit="return confirmReset('<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['nim_nip']) ?>')">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" name="reset_password" class="reset-btn" title="Reset password ke NIM/NIP">
                                🔑 Reset
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?= htmlspecialchars($u['name']) ?>')">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete" class="del-btn">
                                🗑️ Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <h3>Tidak ada user ditemukan</h3>
            <p>Coba ubah filter atau kata kunci pencarian</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function confirmUpdate(name, newRole) {
    return confirm(`Apakah Anda yakin ingin mengubah role user "${name}" menjadi "${newRole.toUpperCase()}"?`);
}

function confirmReset(name, nimNip) {
    return confirm(`⚠️ PERHATIAN!\n\nAnda akan mereset password untuk:\nNama: ${name}\nPassword baru: ${nimNip}\n\nLanjutkan?`);
}

function confirmDelete(name) {
    const confirmed = confirm(`⚠️ PERINGATAN!\n\nAnda akan menghapus user "${name}" secara permanen.\n\nData yang akan hilang:\n- Profil user\n- Riwayat peminjaman\n- Log aktivitas\n\nApakah Anda yakin?`);
    
    if (confirmed) {
        return confirm(`Konfirmasi sekali lagi.\nHapus user "${name}"?`);
    }
    return false;
}

// Auto-hide alert after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

</body>
</html>