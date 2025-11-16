<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

/**
 * Admin Manage Items
 * - Tambah, update, delete item
 * - Delete akan dicek dulu apakah ada dependency di borrowing_details
 */

// Handle tambah item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $category = trim($_POST['category']);
    $stock = $type === 'barang' ? (int)$_POST['stock'] : 1;
    $capacity = $type === 'ruangan' ? (int)$_POST['capacity'] : null;
    $description = trim($_POST['description']);

    // upload image
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'], "../gambar_item/" . $image_name);
    }

    $stmt = $conn->prepare("INSERT INTO items (name, type, category, stock, capacity, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $name, $type, $category, $stock, $capacity, $description, $image_name);
    if ($stmt->execute()) {
        echo "<script>alert('Item berhasil ditambahkan!');</script>";
    } else {
        echo "<script>alert('Gagal menambah item: " . addslashes($stmt->error) . "');</script>";
    }
    $stmt->close();
}

// Handle update item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 1;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // handle image update
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['image']['name']));
        move_uploaded_file($_FILES['image']['tmp_name'], "../gambar_item/" . $image_name);
    }

    if ($image_name !== null) {
        // include image in update
        $stmt = $conn->prepare("UPDATE items SET name=?, category=?, stock=?, capacity=?, description=?, is_active=?, image=? WHERE id=?");
        $stmt->bind_param("ssiisisi", $name, $category, $stock, $capacity, $description, $is_active, $image_name, $id);
    } else {
        // without image
        $stmt = $conn->prepare("UPDATE items SET name=?, category=?, stock=?, capacity=?, description=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssiisii", $name, $category, $stock, $capacity, $description, $is_active, $id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Item berhasil diupdate!');</script>";
    } else {
        echo "<script>alert('Gagal update item: " . addslashes($stmt->error) . "');</script>";
    }
    $stmt->close();
}

// Handle delete item (dengan pengecekan FK)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // cek apakah item direferensi di borrowing_details
    $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM borrowing_details WHERE item_id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'] ?? 0;
    $chk->close();

    if ($cnt > 0) {
        // item masih dipakai, batalkan penghapusan
        echo "<script>alert('Tidak bisa menghapus item ini karena masih digunakan di peminjaman. Hapus dependensi peminjaman terlebih dahulu.'); window.location.href='admin_manage_items.php';</script>";
        exit;
    } else {
        // ambil nama file gambar (untuk dihapus dari server jika ada)
        $g = $conn->prepare("SELECT image FROM items WHERE id = ?");
        $g->bind_param("i", $id);
        $g->execute();
        $resg = $g->get_result()->fetch_assoc();
        $g->close();

        // hapus record
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // jika ada file gambar, hapus file fisik
            if (!empty($resg['image']) && file_exists("../gambar_item/" . $resg['image'])) {
                @unlink("../gambar_item/" . $resg['image']);
            }
            header('Location: admin_manage_items.php');
            exit;
        } else {
            echo "<script>alert('Gagal menghapus item: " . addslashes($stmt->error) . "'); window.location.href='admin_manage_items.php';</script>";
            exit;
        }
    }
}

// Ambil semua items
$items = mysqli_query($conn, "SELECT * FROM items ORDER BY type, category, name");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Barang & Ruangan - Admin</title>
<style>
/* (style sama seperti sebelumnya) */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
.navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
.navbar h1 { font-size: 1.5rem; }
.nav-links { display: flex; gap: 1rem; }
.nav-links a { background: #3498db; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
.container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
.header { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.add-form { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; }
.form-group.full { grid-column: 1 / -1; }
.btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
.btn-primary { background: #3498db; color: white; }
.btn-primary:hover { background: #2980b9; }
.btn-danger { background: #e74c3c; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; }
.btn-edit { background: #f39c12; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; }
.table-container { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
table { width: 100%; border-collapse: collapse; }
th { background: #34495e; color: white; padding: 1rem; text-align: left; }
td { padding: 1rem; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
.status-active { color: #2ecc71; font-weight: bold; }
.status-inactive { color: #e74c3c; font-weight: bold; }
.action-btns { display: flex; gap: 0.5rem; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
.modal-content { background: white; margin: 2rem auto; padding: 2rem; width: 600px; border-radius: 8px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.close { font-size: 2rem; cursor: pointer; color: #7f8c8d; }
.close:hover { color: #2c3e50; }
img.item-image { width: 80px; height: auto; border-radius: 4px; }
</style>
</head>
<body>
<nav class="navbar">
<h1>📦 Kelola Barang & Ruangan</h1>
<div class="nav-links">
<a href="admin_dashboard.php">Dashboard</a>
<a href="admin_manage_borrowings.php">Kelola Peminjaman</a>
<a href="logout_admin.php">Logout</a>
</div>
</nav>

<div class="container">
<div class="header">
<h2>Manajemen Barang & Ruangan</h2>
<p>Tambah, edit, atau hapus barang dan ruangan yang tersedia</p>
</div>

<div class="add-form">
<h3 style="margin-bottom: 1.5rem;">Tambah Item Baru</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-grid">
<div class="form-group">
<label>Nama Item *</label>
<input type="text" name="name" required>
</div>
<div class="form-group">
<label>Tipe *</label>
<select name="type" id="type" required onchange="toggleFields()">
<option value="">Pilih Tipe</option>
<option value="barang">Barang</option>
<option value="ruangan">Ruangan</option>
</select>
</div>
<div class="form-group">
<label>Kategori *</label>
<input type="text" name="category" required placeholder="e.g. Elektronik, Kelas, Lab">
</div>
<div class="form-group" id="stock-group">
<label>Stok *</label>
<input type="number" name="stock" value="1" min="1">
</div>
<div class="form-group" id="capacity-group" style="display: none;">
<label>Kapasitas (orang)</label>
<input type="number" name="capacity" min="1">
</div>
<div class="form-group full">
<label>Deskripsi</label>
<textarea name="description" rows="3" placeholder="Deskripsi item..."></textarea>
</div>
<div class="form-group full">
<label>Gambar Item</label>
<input type="file" name="image" accept="image/*">
</div>
</div>
<button type="submit" name="add_item" class="btn btn-primary">Tambah Item</button>
</form>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th>
<th>Nama</th>
<th>Tipe</th>
<th>Kategori</th>
<th>Stok/Kapasitas</th>
<th>Status</th>
<th>Gambar</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php while ($item = mysqli_fetch_assoc($items)): ?>
<tr>
<td><?= $item['id'] ?></td>
<td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
<td><?= $item['type'] === 'barang' ? '📦 Barang' : '🏢 Ruangan' ?></td>
<td><?= htmlspecialchars($item['category']) ?></td>
<td>
<?php if ($item['type'] === 'barang'): ?>
Stok: <?= $item['stock'] ?>
<?php else: ?>
<?= $item['capacity'] ? $item['capacity'] . ' orang' : '-' ?>
<?php endif; ?>
</td>
<td class="<?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
<?= $item['is_active'] ? 'Aktif' : 'Nonaktif' ?>
</td>
<td>
<?php if($item['image']): ?>
<img src="../gambar_item/<?= $item['image'] ?>" alt="item" class="item-image">
<?php else: ?>
-
<?php endif; ?>
</td>
<td>
<div class="action-btns">
<button onclick='editItem(<?= htmlspecialchars(json_encode($item)) ?>)' class="btn btn-edit">Edit</button>
<a href="?delete=<?= $item['id'] ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus item ini?')">Hapus</a>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="modal">
<div class="modal-content">
<div class="modal-header">
<h3>Edit Item</h3>
<span class="close" onclick="closeModal()">&times;</span>
</div>
<form method="POST" id="editForm" enctype="multipart/form-data">
<input type="hidden" name="id" id="edit_id">
<input type="hidden" id="edit_type_hidden">
<div class="form-group">
<label>Nama Item *</label>
<input type="text" name="name" id="edit_name" required>
</div>
<div class="form-group">
<label>Kategori *</label>
<input type="text" name="category" id="edit_category" required>
</div>
<div class="form-group" id="edit_stock_group">
<label>Stok *</label>
<input type="number" name="stock" id="edit_stock" min="1">
</div>
<div class="form-group" id="edit_capacity_group" style="display: none;">
<label>Kapasitas (orang)</label>
<input type="number" name="capacity" id="edit_capacity" min="1">
</div>
<div class="form-group">
<label>Deskripsi</label>
<textarea name="description" id="edit_description" rows="3"></textarea>
</div>
<div class="form-group">
<label>
<input type="checkbox" name="is_active" id="edit_is_active" value="1"> Aktif
</label>
</div>
<div class="form-group">
<label>Gambar Baru (opsional)</label>
<input type="file" name="image" accept="image/*">
</div>
<button type="submit" name="update_item" class="btn btn-primary">Update Item</button>
</form>
</div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('type').value;
    const stockGroup = document.getElementById('stock-group');
    const capacityGroup = document.getElementById('capacity-group');
    
    if (type === 'barang') {
        stockGroup.style.display = 'block';
        capacityGroup.style.display = 'none';
    } else if (type === 'ruangan') {
        stockGroup.style.display = 'none';
        capacityGroup.style.display = 'block';
    }
}

function editItem(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_category').value = item.category;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_is_active').checked = item.is_active == 1;
    document.getElementById('edit_type_hidden').value = item.type;
    
    if (item.type === 'barang') {
        document.getElementById('edit_stock_group').style.display = 'block';
        document.getElementById('edit_capacity_group').style.display = 'none';
        document.getElementById('edit_stock').value = item.stock;
    } else {
        document.getElementById('edit_stock_group').style.display = 'none';
        document.getElementById('edit_capacity_group').style.display = 'block';
        document.getElementById('edit_capacity').value = item.capacity || '';
    }
    
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
</body>
</html>
