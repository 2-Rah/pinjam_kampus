<?php
require '../config.php';
session_start();

// hanya admin yg boleh buka
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// update role
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_manage_role.php");
    exit;
}

// delete user
if (isset($_POST['delete'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_manage_role.php");
    exit;
}

// ambil semua data user
$data = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Role User</title>
<style>
    body { font-family: 'Segoe UI', Tahoma; background: #f5f5f5; margin:0; padding:0; }
    .navbar { background:#2c3e50; padding:1rem 2rem; color:white; display:flex; justify-content:space-between; align-items:center; }
    .navbar a { color:white; text-decoration:none; }
    .container { max-width:1000px; margin:2rem auto; background:white; padding:2rem; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
    table { width:100%; border-collapse: collapse; margin-top:1rem; }
    th, td { border-bottom: 1px solid #ddd; padding:12px; text-align:left; }
    th { background:#ecf0f1; }
    button { background:#3498db; border:none; padding:8px 15px; border-radius:4px; color:white; cursor:pointer; }
    button:hover { background:#2980b9; }
    .del-btn { background:#e74c3c; }
    .del-btn:hover { background:#c0392b; }
    select { padding:6px; }
</style>
</head>
<body>

<nav class="navbar">
    <div><strong>📦 Admin - Kelola Role User</strong></div>
    <div>
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="logout_admin.php">Logout</a>
    </div>
</nav>

<div class="container">
    <h2>Kelola Role User</h2>

    <table>
    <tr>
        <th>Nama</th>
        <th>NIM/NIP</th>
        <th>Role</th>
        <th>Action</th>
    </tr>

<?php while ($u = mysqli_fetch_assoc($data)) { ?>
<tr>
    <form method="POST" onsubmit="return confirmSubmit(event,'<?= htmlspecialchars($u['name']) ?>')">
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['nim_nip']) ?></td>

        <td>
            <select name="role">
                <option value="none" <?= $u['role']=="none"?"selected":""; ?>>none</option>
                <option value="user" <?= $u['role']=="user"?"selected":""; ?>>user</option>
                <option value="admin" <?= $u['role']=="admin"?"selected":""; ?>>admin</option>
            </select>
        </td>

        <td style="display:flex; gap:8px;">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" name="update">UPDATE</button>
            <button type="submit" name="delete" class="del-btn">DELETE</button>
        </td>
    </form>
</tr>
<?php } ?>

    </table>
</div>

</body>
</html>
