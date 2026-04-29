<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: ../login.php');
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'wedding_organizer';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Koneksi gagal");

$userId = $_SESSION['user']['id'];
$message = '';

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CREATE Order
    if ($action === 'create_order') {
        $package_name = $conn->real_escape_string($_POST['package_name']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $guest_count = (int)$_POST['guest_count'];
        $notes = $conn->real_escape_string($_POST['notes']);
        
        $sql = "INSERT INTO orders (user_id, package_name, event_date, guest_count, notes, status) 
                VALUES ($userId, '$package_name', '$event_date', $guest_count, '$notes', 'Pending')";
        
        if ($conn->query($sql)) {
            $message = '<div class="alert success">✅ Pesanan berhasil dibuat!</div>';
        } else {
            $message = '<div class="alert error">❌ Gagal membuat pesanan!</div>';
        }
    }
    
    // UPDATE Order
    if ($action === 'update_order') {
        $order_id = (int)$_POST['order_id'];
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $guest_count = (int)$_POST['guest_count'];
        $notes = $conn->real_escape_string($_POST['notes']);
        
        $sql = "UPDATE orders SET event_date='$event_date', guest_count=$guest_count, notes='$notes' 
                WHERE id=$order_id AND user_id=$userId";
        
        if ($conn->query($sql)) {
            $message = '<div class="alert success">✅ Pesanan berhasil diupdate!</div>';
        }
    }
    
    // DELETE Order
    if ($action === 'delete_order') {
        $order_id = (int)$_POST['order_id'];
        $sql = "DELETE FROM orders WHERE id=$order_id AND user_id=$userId";
        if ($conn->query($sql)) {
            $message = '<div class="alert success">✅ Pesanan dihapus!</div>';
        }
    }
}

// Get orders
$orders = $conn->query("SELECT * FROM orders WHERE user_id=$userId ORDER BY created_at DESC");

// Get stats
$totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id=$userId")->fetch_assoc()['total'];
$pendingOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE user_id=$userId AND status='Pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | Amore Wedding</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
        }
        
        /* Sidebar */
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
            font-size: 24px;
        }
        
        .sidebar h2 i {
            margin-right: 10px;
            color: #fbbf24;
        }
        
        .sidebar .user-info {
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .sidebar .user-info h4 {
            margin-bottom: 5px;
        }
        
        .sidebar .user-info p {
            font-size: 12px;
            opacity: 0.7;
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
        
        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            background: rgba(239,68,68,0.2);
            color: #f87171;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        
        /* Header */
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
        
        .header p {
            color: #64748b;
            margin-top: 5px;
        }
        
        /* Stats Cards */
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
            color: white;
            font-size: 20px;
        }
        
        .stat-info h3 {
            font-size: 28px;
            color: #1e293b;
        }
        
        .stat-info p {
            color: #64748b;
            font-size: 13px;
        }
        
        /* Cards & Tables */
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
        
        .card-header h3 {
            color: #1e293b;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
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
            font-weight: 600;
            font-size: 13px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
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
        
        .modal-content h3 {
            margin-bottom: 20px;
        }
        
        .modal-content input, .modal-content textarea, .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        
        .alert.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-ring"></i> Amore<br>Wedding</h2>
        <div class="user-info">
            <h4><?= htmlspecialchars($_SESSION['user']['name']) ?></h4>
            <p>Client</p>
        </div>
        <nav>
            <a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#"><i class="fas fa-shopping-cart"></i> Pesanan Saya</a>
            <a href="#"><i class="fas fa-box"></i> Paket Wedding</a>
            <a href="#"><i class="fas fa-store"></i> Vendor</a>
            <a href="#" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['user']['name']) ?>! 💍</h1>
            <p>Kelola pesanan pernikahan Anda di sini</p>
        </div>
        
        <?= $message ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?= $totalOrders ?></h3>
                    <p>Total Pesanan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?= $pendingOrders ?></h3>
                    <p>Menunggu Konfirmasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= $totalOrders - $pendingOrders ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Daftar Pesanan Saya</h3>
                <button class="btn btn-primary" onclick="openModal('createModal')">
                    <i class="fas fa-plus"></i> Buat Pesanan
                </button>
            </div>
            
            <table>
                <thead>
                    <tr><th>Kode</th><th>Paket</th><th>Tanggal Acara</th><th>TamUnda</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['package_name']) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['event_date'])) ?></td>
                            <td><?= $row['guest_count'] ?> orang</td>
                            <td><span class="status-badge status-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn btn-edit" onclick="editOrder(<?= htmlspecialchars(json_encode($row)) ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Yakin hapus?')">
                                    <input type="hidden" name="action" value="delete_order">
                                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-delete"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center">Belum ada pesanan. Buat pesanan pertama Anda!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Create -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h3>Buat Pesanan Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_order">
                <input type="text" name="package_name" placeholder="Nama Paket" required>
                <input type="date" name="event_date" required>
                <input type="number" name="guest_count" placeholder="Jumlah Tamu" required>
                <textarea name="notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <button type="button" class="btn" onclick="closeModal('createModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Pesanan</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="order_id" id="edit_order_id">
                <input type="date" name="event_date" id="edit_event_date" required>
                <input type="number" name="guest_count" id="edit_guest_count" required>
                <textarea name="notes" id="edit_notes" rows="3"></textarea>
                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
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
        
        function editOrder(order) {
            document.getElementById('edit_order_id').value = order.id;
            document.getElementById('edit_event_date').value = order.event_date;
            document.getElementById('edit_guest_count').value = order.guest_count;
            document.getElementById('edit_notes').value = order.notes;
            openModal('editModal');
        }
        
        // Close modal on click outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>