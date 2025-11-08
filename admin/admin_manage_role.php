<?php
require "../db.php";
session_start();

// update role
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $role = $_POST['role'];

    // gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_manage_role.php");
    exit;
}

// ambil semua data user
$data = mysqli_query($conn, "SELECT * FROM users");
?>

<h2>Kelola Role User</h2>

<table border="1" cellpadding="6">
<tr>
    <th>Nama</th>
    <th>NIM/NIP</th>
    <th>Role</th>
    <th>Action</th>
</tr>

<?php while ($u = mysqli_fetch_assoc($data)) { ?>
<tr>
    <form method="POST">
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['nim_nip']) ?></td>
    <td>
        <select name="role">
            <option value="none" <?= $u['role']=="none"?"selected":""; ?>>none</option>
            <option value="user" <?= $u['role']=="user"?"selected":""; ?>>user</option>
            <option value="admin" <?= $u['role']=="admin"?"selected":""; ?>>admin</option>
        </select>
    </td>
    <td>
        <input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button type="submit" name="update">UPDATE</button>
    </td>
    </form>
</tr>
<?php } ?>
</table>
