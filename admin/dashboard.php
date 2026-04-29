<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'wedding_organizer';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Koneksi gagal");

$message = '';

// CRUD for Packages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE Package
    if (isset($_POST['create_package'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $price = (float)$_POST['price'];
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "INSERT INTO packages (name, type, price, description) VALUES ('$name', '$type', $price, '$description')";
        if ($conn->query($sql)) $message = '<div class="alert success">✅ Paket berhasil ditambahkan!</div>';
    }
    
    // UPDATE Package
    if (isset($_POST['update_package'])) {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $price = (float)$_POST['price'];
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "UPDATE packages SET name='$name', type='$type', price=$price, description='$description' WHERE id=$id";
        if ($conn->query($sql)) $message = '<div class="alert success">✅ Paket berhasil diupdate!</div>';
    }
    
    // DELETE Package
    if (isset($_POST['delete_package'])) {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM packages WHERE id=$id";
        if ($conn->query($sql)) $message = '<div class="alert success">✅ Paket dihapus!</div>';
    }
    
    // UPDATE Order Status
    if (isset($_POST['update_status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $sql = "UPDATE orders SET status='$status' WHERE id=$order_id";
        if ($conn->query($sql)) $message = '<div class="alert success">✅ Status diperbarui!</div>';
    }
}

// Get data
$packages = $conn->query("SELECT * FROM packages ORDER BY id DESC");
$orders = $conn->query("SELECT o.*, u.name as client_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$users = $conn->query("SELECT * FROM users WHERE role='client' ORDER BY created_at DESC");
$stats = [
    'packages' => $conn->query("SELECT COUNT(*) as total FROM packages")->fetch_assoc()['total'],
    'orders' => $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'],
    'clients' => $conn->query("SELECT COUNT(*) as total FROM users WHERE role='client'")->fetch_assoc()['total']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Amore Wedding</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 30px 20px;
        }
        
        .sidebar h2 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 30px;
        }
        
        .sidebar h2 i {
            margin-right: 10px;
            color: #fbbf24;
        }
        
        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar nav a i {
            width: 25px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            color: #1e293b;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .stat-info h3 {
            font-size: 28px;
            color: #1e293b;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 5px 12px;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            color: #64748b;
            font-size: 13px;
        }
        
        .status-select {
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d1fae5;
            color: #059669;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-content input, .modal-content textarea, .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-ring"></i> Amore Admin</h2>
        <nav>
            <a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#packages"><i class="fas fa-box"></i> Paket Wedding</a>
            <a href="#orders"><i class="fas fa-shopping-cart"></i> Pesanan</a>
            <a href="#clients"><i class="fas fa-users"></i> Klien</a>
            <a href="../login.php?logout=1" style="margin-top: 50px; color:#f87171"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Halo, Admin <?= htmlspecialchars($_SESSION['user']['name']) ?>! 👋</h1>
            <p>Kelola semua data wedding organizer di sini</p>
        </div>
        
        <?= $message ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-info"><h3><?= $stats['packages'] ?></h3><p>Total Paket</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info"><h3><?= $stats['orders'] ?></h3><p>Total Pesanan</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?= $stats['clients'] ?></h3><p>Klien Aktif</p></div>
            </div>
        </div>
        
        <!-- Packages CRUD -->
        <div class="card" id="packages">
            <div class="card-header">
                <h3><i class="fas fa-box"></i> Manajemen Paket Wedding</h3>
                <button class="btn btn-primary" onclick="openModal('packageModal')">
                    <i class="fas fa-plus"></i> Tambah Paket
                </button>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Nama Paket</th><th>Tipe</th><th>Harga</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php while($pkg = $packages->fetch_assoc()): ?>
                    <tr>
                        <td><?= $pkg['id'] ?></td>
                        <td><?= htmlspecialchars($pkg['name']) ?></td>
                        <td><?= htmlspecialchars($pkg['type']) ?></td>
                        <td>Rp <?= number_format($pkg['price'], 0, ',', '.') ?></td>
                        <td>
                            <button class="btn-edit" onclick="editPackage(<?= htmlspecialchars(json_encode($pkg)) ?>)">Edit</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Yakin hapus?')">
                                <input type="hidden" name="delete_package" value="1">
                                <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Orders Management -->
        <div class="card" id="orders">
            <div class="card-header">
                <h3><i class="fas fa-shopping-cart"></i> Daftar Pesanan</h3>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Klien</th><th>Paket</th><th>Tanggal Acara</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php while($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['client_name']) ?></td>
                        <td><?= htmlspecialchars($order['package_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($order['event_date'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="status-select" onchange="this.form.submit()">
                                    <option value="Pending" <?= $order['status']=='Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Confirmed" <?= $order['status']=='Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="Completed" <?= $order['status']=='Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $order['status']=='Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Yakin hapus pesanan ini?')">
                                <input type="hidden" name="delete_order" value="1">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Package -->
    <div id="packageModal" class="modal">
        <div class="modal-content">
            <h3>Tambah Paket Wedding</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Nama Paket" required>
                <select name="type" required>
                    <option value="Silver">Silver</option>
                    <option value="Gold">Gold</option>
                    <option value="Platinum">Platinum</option>
                    <option value="Diamond">Diamond</option>
                </select>
                <input type="number" name="price" placeholder="Harga" required>
                <textarea name="description" rows="3" placeholder="Deskripsi paket..."></textarea>
                <button type="submit" name="create_package" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn" onclick="closeModal('packageModal')">Batal</button>
            </form>
        </div>
    </div>
    
    <div id="editPackageModal" class="modal">
        <div class="modal-content">
            <h3>Edit Paket</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="text" name="name" id="edit_name" required>
                <select name="type" id="edit_type">
                    <option value="Silver">Silver</option>
                    <option value="Gold">Gold</option>
                    <option value="Platinum">Platinum</option>
                    <option value="Diamond">Diamond</option>
                </select>
                <input type="number" name="price" id="edit_price" required>
                <textarea name="description" id="edit_description" rows="3"></textarea>
                <button type="submit" name="update_package" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="closeModal('editPackageModal')">Batal</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function editPackage(pkg) {
            document.getElementById('edit_id').value = pkg.id;
            document.getElementById('edit_name').value = pkg.name;
            document.getElementById('edit_type').value = pkg.type;
            document.getElementById('edit_price').value = pkg.price;
            document.getElementById('edit_description').value = pkg.description;
            openModal('editPackageModal');
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>