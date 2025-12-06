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
// HANDLE EDIT USER
// ===========================
if (isset($_POST['edit_user'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $nim_nip = trim($_POST['nim_nip']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    // Validasi input
    if (empty($name) || empty($nim_nip) || empty($email)) {
        $error_message = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid!";
    } else {
        // Cek apakah email sudah digunakan oleh user lain
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $error_message = "Email sudah digunakan oleh user lain!";
        } else {
            // Cek apakah NIM/NIP sudah digunakan oleh user lain
            $check_nim = $conn->prepare("SELECT id FROM users WHERE nim_nip = ? AND id != ?");
            $check_nim->bind_param("si", $nim_nip, $id);
            $check_nim->execute();
            $check_nim->store_result();
            
            if ($check_nim->num_rows > 0) {
                $error_message = "NIM/NIP sudah digunakan oleh user lain!";
            } else {
                // Update user
                $allowed_roles = ['none', 'user', 'admin'];
                if (!in_array($role, $allowed_roles)) {
                    $error_message = "Role tidak valid!";
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, nim_nip = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $nim_nip, $email, $role, $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Data user berhasil diperbarui!";
                    } else {
                        $error_message = "Gagal update data user.";
                    }
                    $stmt->close();
                }
            }
            $check_nim->close();
        }
        $check_email->close();
    }
}

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
            $success_message = "Password untuk <strong>" . htmlspecialchars($user_data['name']) . "</strong> berhasil direset ke NIM/NIP: <strong>" . htmlspecialchars($user_data['nim_nip']) . "</strong>";
        } else {
            $error_message = "Gagal reset password.";
        }
        $stmt->close();
    }
    $user_query->close();
}

// ===========================
// HANDLE UPDATE ROLE
// ===========================
if (isset($_POST['update_role'])) {
    $id = intval($_POST['id']);
    $role = $_POST['role'];
    
    $allowed_roles = ['none', 'user', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $error_message = "Role tidak valid!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $id);
        
        if ($stmt->execute()) {
            $success_message = "Role berhasil diupdate!";
        } else {
            $error_message = "Gagal update role.";
        }
        $stmt->close();
    }
}

// ===========================
// HANDLE DELETE USER
// ===========================
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    
    // Cek peminjaman aktif
    $check = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status IN ('pending', 'approved', 'picked_up') UNION ALL SELECT COUNT(*) FROM returns WHERE user_id = ? AND status = 'pending'");
    $check->bind_param("ii", $id, $id);
    $check->execute();
    $results = $check->get_result();
    $borrow_count = $results->fetch_assoc()['count'];
    $return_count = $results->fetch_assoc()['count'];
    
    if ($borrow_count > 0 || $return_count > 0) {
        $error_message = "Tidak dapat menghapus user yang memiliki peminjaman/pengembalian aktif!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "User berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus user.";
        }
        $stmt->close();
    }
    $check->close();
}

// ===========================
// SEARCH & FILTER
// ===========================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) && $_GET['role'] !== 'all' ? $_GET['role'] : '';

$sql = "SELECT id, name, nim_nip, email, role FROM users WHERE 1=1";

$params = [];
$param_types = "";

if ($search !== '') {
    $like = "%$search%";
    $sql .= " AND (name LIKE ? OR nim_nip LIKE ? OR email LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $param_types .= "sss";
}

if ($filter_role !== '') {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
    $param_types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$data = $stmt->get_result();
$total_users = $data->num_rows;

// Store data in array for modal usage
$users_data = [];
while ($row = $data->fetch_assoc()) {
    $users_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User • Admin Dashboard</title>
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

        /* ACTIVE LINK: User */
        .navbar-links a[href="admin_manage_role.php"]::after,
        .navbar-links a[href="admin_manage_role.php"]:hover::after {
            transform: scaleX(1);
        }

        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .user-count {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 8px 20px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Search & Filter */
        .search-filter {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 32px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .btn-search {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
        }

        .btn-reset {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* Alerts */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            /* HAPUS: overflow: hidden; agar dropdown tidak terpotong */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: #475569;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 14px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Role Badges */
        .role-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-admin { background: #ede9fe; color: #7c3aed; }
        .role-user  { background: #dbeafe; color: #2563eb; }
        .role-none  { background: #f1f5f9; color: #64748b; }

        code {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9em;
        }

        /* Action Buttons */
        .action-cell {
            width: 100px;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .btn-edit {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 220px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border-radius: 10px;
            z-index: 1001; /* DITINGKATKAN dari 1000 ke 1001 */
            overflow: hidden;
            border: 1px solid #e2e8f0;
            margin-top: 8px; /* Ditambahkan untuk spacing */
        }

        .dropdown-content form {
            display: block;
            margin: 0;
        }

        .dropdown-content button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
            color: #334155;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }

        .dropdown-content button:last-child {
            border-bottom: none;
        }

        .dropdown-content button:hover {
            background: #f8fafc;
            color: #6366f1;
        }

        .dropdown-content .danger:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Role Select in Table */
        .role-select-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .role-select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            min-width: 100px;
        }

        .role-select:focus {
            outline: none;
            border-color: #6366f1;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 24px 32px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.8;
        }

        .modal-body {
            padding: 32px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .btn-modal {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        /* Empty State */
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

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 20px;
                flex-direction: column;
                gap: 12px;
            }

            .container {
                padding: 0 20px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .role-select-form {
                flex-direction: column;
                align-items: stretch;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            table {
                font-size: 14px;
            }

            td, th {
                padding: 12px;
            }

            .dropdown-content {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                max-width: 300px;
                margin-top: 0; /* Reset untuk mobile */
            }
            
            /* Tambahkan scroll horizontal untuk tabel di mobile */
            .table-container {
                overflow-x: auto;
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
        <a href="admin_manage_returns.php">Pengembalian</a>
        <a href="logout_admin.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>Kelola User</h1>
        <div class="user-count">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Total: <?= $total_users ?> user
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?= $success_message ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="search-filter">
        <form method="GET" style="display: contents;">
            <div class="form-group">
                <label for="search">Cari User</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    class="form-control"
                    placeholder="Nama, NIM/NIP, atau Email..."
                    value="<?= htmlspecialchars($search) ?>"
                >
            </div>

            <div class="form-group">
                <label for="role">Filter Role</label>
                <select id="role" name="role" class="form-control">
                    <option value="all" <?= $filter_role === '' ? 'selected' : '' ?>>Semua Role</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="user" <?= $filter_role === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="none" <?= $filter_role === 'none' ? 'selected' : '' ?>>None</option>
                </select>
            </div>

            <button type="submit" class="btn-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Cari
            </button>
        </form>

        <a href="admin_manage_role.php" class="btn-reset">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reset Filter
        </a>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>NIM/NIP</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Ubah Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_users > 0): ?>
                    <?php foreach ($users_data as $u): ?>
                    <tr>
                        <td><strong>#<?= $u['id'] ?></strong></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><code><?= htmlspecialchars($u['nim_nip']) ?></code></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                        </td>
                        <td>
                            <form method="POST" class="role-select-form" onsubmit="return confirmUpdateRole('<?= htmlspecialchars($u['name']) ?>', this.role.value)">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <select name="role" class="role-select">
                                    <option value="none" <?= $u['role'] === 'none' ? 'selected' : '' ?>>None</option>
                                    <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="update_role" class="btn-edit" style="padding: 8px 12px; font-size: 12px;">
                                    Update
                                </button>
                            </form>
                        </td>
                        <td class="action-cell">
                            <div class="dropdown">
                                <button class="btn-edit">
                                    Edit
                                </button>
                                <div class="dropdown-content">
                                    <button onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['nim_nip'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['role'] ?>')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit Data User
                                    </button>
                                    <form method="POST" onsubmit="return confirmResetPassword('<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['nim_nip']) ?>')">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="reset_password">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Reset Password
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirmDeleteUser('<?= htmlspecialchars($u['name']) ?>')">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="delete" class="danger">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Hapus User
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h3>Tidak ada user ditemukan</h3>
                                <p>Coba ubah filter atau kata kunci pencarian.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Data User</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="editUserForm">
                <input type="hidden" name="id" id="edit_user_id">
                
                <div class="form-row">
                    <label for="edit_name">Nama Lengkap</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <label for="edit_nim_nip">NIM / NIP</label>
                    <input type="text" id="edit_nim_nip" name="nim_nip" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" class="form-control" required>
                        <option value="none">None</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" name="edit_user" class="btn-modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openEditModal(id, name, nimNip, email, role) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_nim_nip').value = nimNip;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}

// Confirmation functions
function confirmUpdateRole(name, newRole) {
    return confirm(`Ubah role user "${name}" menjadi "${newRole}"?`);
}

function confirmResetPassword(name, nimNip) {
    return confirm(`Reset password untuk user:\nNama: ${name}\nPassword baru: ${nimNip}\n\nLanjutkan?`);
}

function confirmDeleteUser(name) {
    if (!confirm(`Hapus user "${name}"?\nUser dengan peminjaman aktif tidak dapat dihapus.`)) return false;
    return confirm(`Konfirmasi akhir: hapus "${name}" secara permanen?`);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 6000);
    });
});

// Improved dropdown handling untuk mencegah terpotong
document.querySelectorAll('.dropdown').forEach(dropdown => {
    const btn = dropdown.querySelector('.btn-edit');
    const content = dropdown.querySelector('.dropdown-content');
    
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Tutup semua dropdown lainnya
        document.querySelectorAll('.dropdown-content').forEach(other => {
            if (other !== content) other.style.display = 'none';
        });
        
        // Toggle dropdown saat ini
        if (content.style.display === 'block') {
            content.style.display = 'none';
        } else {
            content.style.display = 'block';
        }
    });
});

// Tutup dropdown saat klik di luar
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content').forEach(content => {
            content.style.display = 'none';
        });
    }
});
</script>

</body>
</html>