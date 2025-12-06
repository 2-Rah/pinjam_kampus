<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$total_items = count($cart);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Peminjaman • Sistem Peminjaman</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .cart-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            border: 2px solid #667eea;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        .empty-state img {
            width: 200px;
            height: 200px;
            opacity: 0.7;
            margin-bottom: 24px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #4a5568;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }

        /* Cart Items */
        .cart-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 18px 24px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .cart-table td {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .cart-table tr:hover {
            background: #f8fafc;
        }

        /* Item Image */
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .item-image:hover {
            transform: scale(1.05);
        }

        /* Item Details */
        .item-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 17px;
            margin-bottom: 4px;
        }

        .item-code {
            color: #718096;
            font-size: 14px;
            font-family: monospace;
            background: #f7fafc;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Quantity */
        .quantity-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quantity-badge {
            background: linear-gradient(135deg, #edf2f7, #e2e8f0);
            color: #2d3748;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            min-width: 60px;
            text-align: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Stock Indicator */
        .stock-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
        }

        .stock-available {
            background: #c6f6d5;
            color: #22543d;
        }

        .stock-low {
            background: #fed7d7;
            color: #742a2a;
        }

        .stock-icon {
            font-size: 18px;
        }

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #fc8181, #f56565);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
        }

        /* Cart Footer */
        .cart-footer {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-buttons {
            display: flex;
            gap: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }

        .summary {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .total-items {
            background: #edf2f7;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: #4a5568;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .cart-info {
                flex-direction: column;
                gap: 10px;
            }

            .cart-table {
                display: block;
                overflow-x: auto;
            }

            .cart-table th,
            .cart-table td {
                padding: 12px;
                font-size: 14px;
            }

            .item-image {
                width: 70px;
                height: 70px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .cart-footer {
                flex-direction: column;
                text-align: center;
            }

            .footer-buttons {
                width: 100%;
                flex-direction: column;
            }

            .nav-links {
                flex-direction: column;
                width: 100%;
            }

            .nav-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cart-container, .empty-state {
            animation: fadeIn 0.5s ease-out;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Keranjang Peminjaman
            </h1>
            <div class="cart-info">
                <div class="cart-count">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <?= $total_items ?> Barang
                </div>
                <div class="nav-links">
                    <a href="barang_list.php" class="nav-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"></path>
                        </svg>
                        Tambah Barang
                    </a>
                    <a href="dashboard.php" class="nav-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($cart)): ?>
            <!-- Empty Cart State -->
            <div class="empty-state">
                <svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="#cbd5e0" stroke-width="1">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h3>Keranjang Masih Kosong</h3>
                <p>Belum ada barang yang ditambahkan ke keranjang peminjaman</p>
                <a href="barang_list.php" class="btn btn-primary" style="display: inline-flex; width: auto; padding: 12px 30px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Pilih Barang
                </a>
            </div>

        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-container">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Detail</th>
                            <th>Jumlah</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $c): 
                            $stock_class = $c['quantity'] > $c['stock'] ? 'stock-low' : 'stock-available';
                            $stock_text = $c['quantity'] > $c['stock'] ? 'Stok Kurang' : 'Stok Tersedia';
                        ?>
                        <tr>
                            <td>
                                <img src="../gambar_item/<?= htmlspecialchars($c['image']) ?>" 
                                     alt="<?= htmlspecialchars($c['name']) ?>" 
                                     class="item-image"
                                     onerror="this.src='https://via.placeholder.com/100x100?text=No+Image'">
                            </td>
                            <td>
                                <div class="item-name"><?= htmlspecialchars($c['name']) ?></div>
                                <?php if (!empty($c['code'])): ?>
                                    <div class="item-code"><?= htmlspecialchars($c['code']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="quantity-info">
                                    <div class="quantity-badge"><?= $c['quantity'] ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="stock-info">
                                    <div class="stock-badge <?= $stock_class ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="stock-icon">
                                            <?php if ($c['quantity'] > $c['stock']): ?>
                                                <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            <?php else: ?>
                                                <path d="M5 13l4 4L19 7"></path>
                                            <?php endif; ?>
                                        </svg>
                                        <?= $stock_text ?> (<?= $c['stock'] ?>)
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="cart_remove.php?id=<?= $c['id'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Hapus <?= htmlspecialchars(addslashes($c['name'])) ?> dari keranjang?')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cart Footer -->
            <div class="cart-footer">
                <div class="summary">
                    <div class="total-items">
                        Total: <?= $total_items ?> Barang
                    </div>
                </div>
                <div class="footer-buttons">
                    <a href="barang_list.php" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"></path>
                        </svg>
                        Tambah Barang Lain
                    </a>
                    <a href="pinjam_form.php" class="btn btn-success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 13l4 4L19 7"></path>
                        </svg>
                        Lanjutkan Peminjaman
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to buttons
            const buttons = document.querySelectorAll('.btn, .nav-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Check for low stock items
            const stockBadges = document.querySelectorAll('.stock-low');
            if (stockBadges.length > 0) {
                console.log('Ada barang dengan stok kurang di keranjang');
            }
        });
    </script>
</body>
</html>