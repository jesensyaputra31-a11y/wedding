<?php
session_start();
require_once 'php/config.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

$adminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';

// ========== BUAT TABEL WEDDINGS JIKA BELUM ADA ==========
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'weddings'");
if(mysqli_num_rows($table_check) == 0) {
    mysqli_query($conn, "CREATE TABLE weddings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        couple VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        guests INT(11) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'Planning',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    mysqli_query($conn, "INSERT INTO weddings (couple, date, guests, status) VALUES 
        ('Sarah & David', '2025-12-25', 250, 'Planning'),
        ('Amanda & Budi', '2026-01-15', 180, 'Pending'),
        ('Jessica & Rizki', '2026-02-10', 320, 'Planning'),
        ('Putri & Andi', '2026-03-20', 210, 'Confirmed'),
        ('Maya & Dimas', '2026-04-05', 150, 'Planning')");
}

// ========== BUAT TABEL PLANNER RATINGS JIKA BELUM ADA ==========
$rating_table = mysqli_query($conn, "SHOW TABLES LIKE 'planner_ratings'");
if(mysqli_num_rows($rating_table) == 0) {
    mysqli_query($conn, "CREATE TABLE planner_ratings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        planner_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        rating INT(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_rating (planner_id, user_id)
    )");
}

// ========== AMBIL DATA WEDDINGS ==========
$recent_weddings = mysqli_query($conn, "SELECT * FROM weddings ORDER BY id DESC LIMIT 3");
$all_weddings = mysqli_query($conn, "SELECT * FROM weddings ORDER BY id DESC");

// ========== PROSES AJAX ==========
if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_wedding') {
    $id = intval($_POST['id']);
    $couple = mysqli_real_escape_string($conn, $_POST['couple']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $guests = intval($_POST['guests']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if($id > 0) {
        $query = "UPDATE weddings SET couple='$couple', date='$date', guests=$guests, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO weddings (couple, date, guests, status) VALUES ('$couple', '$date', $guests, '$status')";
    }
    
    if(mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Wedding saved!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_wedding') {
    $id = intval($_POST['id']);
    $query = "DELETE FROM weddings WHERE id=$id";
    if(mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Wedding deleted!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_invitation') {
    $guest_name = mysqli_real_escape_string($conn, $_POST['guest_name']);
    $guest_email = mysqli_real_escape_string($conn, $_POST['guest_email']);
    $wedding_id = mysqli_real_escape_string($conn, $_POST['wedding_id']);
    
    $inv_table = mysqli_query($conn, "SHOW TABLES LIKE 'invitations'");
    if(mysqli_num_rows($inv_table) == 0) {
        mysqli_query($conn, "CREATE TABLE invitations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            guest_name VARCHAR(100) NOT NULL,
            guest_email VARCHAR(100) NOT NULL,
            wedding_id VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'sent',
            sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )");
    }
    
    $query = "INSERT INTO invitations (guest_name, guest_email, wedding_id, status, sent_date) 
              VALUES ('$guest_name', '$guest_email', '$wedding_id', 'sent', NOW())";
    
    if(mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Invitation sent!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_payment') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $pay_table = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
    if(mysqli_num_rows($pay_table) == 0) {
        mysqli_query($conn, "CREATE TABLE payments (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id VARCHAR(50) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'Bank Transfer',
            status VARCHAR(20) DEFAULT 'completed',
            notes TEXT,
            payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )");
    }
    
    $query = "INSERT INTO payments (user_id, amount, payment_method, status, notes, payment_date) 
              VALUES ('$user_id', '$amount', '$payment_method', 'completed', '$notes', NOW())";
    
    if(mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Payment recorded!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

// ========== AMBIL DATA USERS (PERBAIKAN) ==========
// Gabungkan users dan admins menjadi satu tampilan
$all_users_data = mysqli_query($conn, "
    SELECT id, username, email, role, created_at, 'users' as source 
    FROM users 
    UNION 
    SELECT id, username, email, role, created_at, 'admins' as source 
    FROM admins 
    ORDER BY id DESC
");

// Recent users (5 terbaru dari gabungan)
$recent_users_data = mysqli_query($conn, "
    SELECT id, username, email, role, created_at, 'users' as source 
    FROM users 
    UNION 
    SELECT id, username, email, role, created_at, 'admins' as source 
    FROM admins 
    ORDER BY id DESC 
    LIMIT 5
");

// Hitung total users (dari tabel users, bukan admin)
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];

// Hitung total admins (dari tabel admins)
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM admins"))['count'];

$total_users_only = $total_users;

$total_invitations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM invitations"))['count'] ?? 0;
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='completed'"))['total'] ?? 0;

// ========== AMBIL SEMUA RATING DARI USER ==========
$total_ratings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM planner_ratings"))['count'] ?? 0;

// Cek apakah tabel wedding_planners ada
$planners_table = mysqli_query($conn, "SHOW TABLES LIKE 'wedding_planners'");
if(mysqli_num_rows($planners_table) > 0) {
    $all_ratings = mysqli_query($conn, "
        SELECT pr.*, wp.name as planner_name, wp.photo_url, u.username as user_name 
        FROM planner_ratings pr 
        JOIN wedding_planners wp ON pr.planner_id = wp.id 
        JOIN users u ON pr.user_id = u.id 
        ORDER BY pr.created_at DESC
    ");
} else {
    $all_ratings = mysqli_query($conn, "SELECT * FROM planner_ratings WHERE 1=0");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard - Wedding Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .sidebar {
            position: fixed; left: 0; top: 0; width: 280px; height: 100%;
            background: linear-gradient(135deg, #1a1a2e, #16213e); color: white;
            transition: all 0.3s; z-index: 100; overflow-y: auto;
        }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-family: 'Cormorant Garamond', serif; font-size: 28px; background: linear-gradient(135deg, #FFD700, #F5A623); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-header p { font-size: 11px; opacity: 0.6; margin-top: 5px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.3s; color: rgba(255,255,255,0.7); }
        .menu-item:hover, .menu-item.active { background: rgba(212, 175, 55, 0.15); color: #D4AF37; border-left: 3px solid #D4AF37; }
        .menu-item i { width: 22px; font-size: 18px; }
        .menu-item span { font-size: 14px; }

        .main-content { margin-left: 280px; padding: 20px; }
        .top-nav {
            background: white; border-radius: 16px; padding: 15px 25px;
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .page-title h1 { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: #1a1a2e; }
        .admin-info { display: flex; align-items: center; gap: 20px; }
        .admin-info .badge { background: linear-gradient(135deg, #D4AF37, #B8860B); padding: 8px 20px; border-radius: 40px; color: white; font-size: 13px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 20px; border-radius: 40px; text-decoration: none; font-size: 13px; }
        .logout-btn:hover { background: #c0392b; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card {
            background: white; padding: 20px; border-radius: 20px; display: flex;
            align-items: center; justify-content: space-between; cursor: pointer;
            transition: all 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-info h3 { font-size: 28px; font-weight: 700; color: #1a1a2e; }
        .stat-info p { font-size: 13px; color: #888; margin-top: 5px; }
        .stat-icon { width: 55px; height: 55px; background: #fef5e8; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .stat-icon i { font-size: 28px; color: #D4AF37; }

        .quick-actions { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; }
        .section-title { font-family: 'Cormorant Garamond', serif; font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0e5dc; }
        .actions-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; }
        .action-btn {
            text-align: center; padding: 15px; border-radius: 16px; cursor: pointer;
            transition: all 0.3s; background: #f8f6f3;
        }
        .action-btn:hover { background: linear-gradient(135deg, #D4AF37, #B8860B); transform: translateY(-3px); }
        .action-btn:hover i, .action-btn:hover span { color: white; }
        .action-btn i { font-size: 28px; color: #D4AF37; margin-bottom: 8px; display: block; }
        .action-btn span { font-size: 11px; color: #666; }

        .data-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .view-all { color: #D4AF37; font-size: 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #f0e5dc; font-size: 13px; vertical-align: middle; }
        th { color: #D4AF37; font-weight: 600; background: #faf8f5; }
        tr:hover { background: #fef5e8; }
        .row-clickable { cursor: pointer; }
        .role-badge { background: #D4AF37; color: white; padding: 5px 14px; border-radius: 30px; font-size: 11px; display: inline-block; min-width: 70px; text-align: center; }
        .role-badge.admin { background: #e74c3c; }
        .role-badge.user, .role-badge.client { background: #3498db; }
        .status-badge { padding: 5px 14px; border-radius: 30px; font-size: 11px; display: inline-block; min-width: 85px; text-align: center; }
        .status-active, .status-confirmed, .status-sent, .status-completed { background: #2ecc71; color: white; }
        .status-pending { background: #f39c12; color: white; }
        .status-planning { background: #3498db; color: white; }
        .edit-wedding, .delete-wedding { cursor: pointer; transition: all 0.3s; font-size: 16px; margin: 0 5px; }
        .edit-wedding { color: #D4AF37; }
        .edit-wedding:hover { color: #B8860B; transform: scale(1.1); }
        .delete-wedding { color: #e74c3c; }
        .delete-wedding:hover { color: #c0392b; transform: scale(1.1); }

        .rating-stars { display: inline-flex; gap: 2px; }
        .rating-stars i { font-size: 14px; }

        .settings-list { margin-top: 20px; }
        .setting-item { padding: 18px; border-bottom: 1px solid #f0e5dc; cursor: pointer; display: flex; align-items: center; gap: 15px; transition: all 0.3s; }
        .setting-item:hover { background: #fef5e8; padding-left: 25px; }
        .setting-item i { font-size: 20px; color: #D4AF37; width: 35px; }
        .setting-info { flex: 1; }
        .setting-name { font-weight: 600; color: #1a1a2e; }
        .setting-value { font-size: 12px; color: #888; margin-top: 4px; }

        .request-card { background: #f8f6f3; border-radius: 16px; padding: 18px; margin-bottom: 15px; border-left: 4px solid #D4AF37; transition: all 0.3s; }
        .request-card:hover { background: #fef5e8; transform: translateX(5px); }
        .request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .request-feature { font-weight: 700; color: #D4AF37; font-size: 16px; }
        .request-user { color: #1a1a2e; font-size: 13px; background: #f0e5dc; padding: 4px 12px; border-radius: 20px; }
        .request-date { font-size: 11px; color: #888; }
        .request-details { color: #4a3b2c; font-size: 13px; line-height: 1.5; margin: 10px 0; padding: 10px; background: white; border-radius: 12px; }
        .request-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-pending { background: #f39c12; color: white; }
        .status-approved { background: #2ecc71; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        .request-actions { display: flex; gap: 10px; margin-top: 12px; }
        .request-actions button { padding: 6px 16px; border: none; border-radius: 20px; cursor: pointer; font-size: 12px; }
        .btn-approve { background: #2ecc71; color: white; }
        .btn-reject { background: #e74c3c; color: white; }
        .btn-delete { background: #95a5a6; color: white; }
        .empty-state { text-align: center; padding: 40px; color: #888; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 24px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; animation: fadeInUp 0.3s ease; }
        .modal-content h3 { font-family: 'Cormorant Garamond', serif; font-size: 28px; margin-bottom: 20px; color: #1a1a2e; }
        .modal-close { float: right; font-size: 28px; cursor: pointer; color: #888; }
        .modal-close:hover { color: #D4AF37; }
        .detail-item { margin-bottom: 15px; padding: 10px; background: #f8f6f3; border-radius: 12px; }
        .detail-item strong { color: #D4AF37; display: inline-block; width: 100px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #b48c5c; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 12px; font-family: inherit; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #D4AF37, #B8860B); border: none; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; margin-top: 10px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 1100px) {
            .sidebar { width: 80px; }
            .sidebar-header h2, .sidebar-header p, .menu-item span { display: none; }
            .menu-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 80px; }
            .actions-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .actions-grid { grid-template-columns: repeat(2, 1fr); }
            th, td { padding: 10px 8px; font-size: 12px; }
            .role-badge, .status-badge { padding: 4px 8px; min-width: 60px; font-size: 10px; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Wedding<span style="background: none; -webkit-text-fill-color: #D4AF37;">Org</span></h2>
        <p>ADMIN PANEL</p>
    </div>
    <div class="sidebar-menu">
        <div class="menu-item active" data-page="dashboard" onclick="showPage('dashboard')"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></div>
        <div class="menu-item" data-page="users" onclick="showPage('users')"><i class="fas fa-users"></i><span>Users</span></div>
        <div class="menu-item" data-page="weddings" onclick="showPage('weddings')"><i class="fas fa-ring"></i><span>Weddings</span></div>
        <div class="menu-item" data-page="invitations" onclick="showPage('invitations')"><i class="fas fa-envelope-open-text"></i><span>Invitations</span></div>
        <div class="menu-item" data-page="payments" onclick="showPage('payments')"><i class="fas fa-credit-card"></i><span>Payments</span></div>
        <div class="menu-item" data-page="reports" onclick="showPage('reports')"><i class="fas fa-chart-line"></i><span>Reports</span></div>
        <div class="menu-item" data-page="ratings" onclick="showPage('ratings')"><i class="fas fa-star"></i><span>User Ratings</span><span id="ratingsBadge" style="background:#e74c3c; color:white; border-radius:50%; padding:2px 6px; font-size:10px; margin-left:5px; display:<?php echo $total_ratings > 0 ? 'inline-block' : 'none'; ?>;"><?php echo $total_ratings > 0 ? $total_ratings : ''; ?></span></div>
        <div class="menu-item" data-page="requests" onclick="showPage('requests')"><i class="fas fa-bell"></i><span>User Requests</span><span id="requestBadge" style="background:#e74c3c; color:white; border-radius:50%; padding:2px 8px; font-size:10px; margin-left:5px; display:none;">0</span></div>
        <div class="menu-item" data-page="settings" onclick="showPage('settings')"><i class="fas fa-cog"></i><span>Settings</span></div>
    </div>
</div>

<div class="main-content">
    <div class="top-nav">
        <div class="page-title"><h1>Dashboard Overview</h1></div>
        <div class="admin-info">
            <span class="badge"><i class="fas fa-crown"></i> <?php echo htmlspecialchars($adminName); ?></span>
            <a href="admin-logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- DASHBOARD PAGE -->
    <div id="dashboard-page">
        <div class="stats-grid">
            <div class="stat-card" onclick="showNotification('Total Users: <?php echo $total_users; ?>')">
                <div class="stat-info"><h3><?php echo $total_users; ?></h3><p>Total Users</p></div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card" onclick="showNotification('Total Admins: <?php echo $total_admins; ?>')">
                <div class="stat-info"><h3><?php echo $total_admins; ?></h3><p>Total Admins</p></div>
                <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            </div>
            <div class="stat-card" onclick="showNotification('Total Invitations: <?php echo $total_invitations; ?>')">
                <div class="stat-info"><h3><?php echo $total_invitations; ?></h3><p>Invitations</p></div>
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            </div>
            <div class="stat-card" onclick="showNotification('Revenue: Rp <?php echo number_format($total_payments); ?>')">
                <div class="stat-info"><h3>Rp <?php echo number_format($total_payments/1000000, 1); ?>M</h3><p>Revenue</p></div>
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <div class="quick-actions">
            <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
            <div class="actions-grid">
                <div class="action-btn" onclick="showAddUserForm()"><i class="fas fa-user-plus"></i><span>Add User</span></div>
                <div class="action-btn" onclick="showAddWeddingForm()"><i class="fas fa-ring"></i><span>Create Wedding</span></div>
                <div class="action-btn" onclick="showSendInviteForm()"><i class="fas fa-envelope-open-text"></i><span>Send Invites</span></div>
                <div class="action-btn" onclick="showPaymentForm()"><i class="fas fa-credit-card"></i><span>Payments</span></div>
                <div class="action-btn" onclick="showPage('reports')"><i class="fas fa-file-alt"></i><span>Reports</span></div>
                <div class="action-btn" onclick="showPage('settings')"><i class="fas fa-cog"></i><span>Settings</span></div>
            </div>
        </div>

        <!-- RECENT USERS (PERBAIKAN) -->
        <div class="data-card">
            <div class="table-header">
                <div class="section-title" style="margin-bottom: 0;">📋 Recent Users</div>
                <div class="view-all" onclick="showPage('users')">View All →</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($recent_users_data) > 0): ?>
                            <?php while($user = mysqli_fetch_assoc($recent_users_data)): ?>
                            <tr class="row-clickable" onclick='showUserDetail(<?php echo json_encode($user); ?>)'>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="role-badge <?php echo $user['role'] === 'admin' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding:40px;">No users found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RECENT WEDDINGS -->
        <div class="data-card" style="margin-top: 25px;">
            <div class="table-header">
                <div class="section-title" style="margin-bottom: 0;">💍 Recent Weddings</div>
                <div class="view-all" onclick="showPage('weddings')">View All →</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>Couple</th><th>Date</th><th>Guests</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="recent-weddings-tbody">
                        <?php 
                        $recentWeds = mysqli_query($conn, "SELECT * FROM weddings ORDER BY id DESC LIMIT 3");
                        while($w = mysqli_fetch_assoc($recentWeds)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($w['couple']); ?></td>
                            <td><?php echo date('d M Y', strtotime($w['date'])); ?></td>
                            <td><?php echo $w['guests']; ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($w['status']); ?>"><?php echo $w['status']; ?></span></td>
                            <td><i class="fas fa-edit edit-wedding" onclick='editWedding(<?php echo $w['id']; ?>, "<?php echo addslashes($w['couple']); ?>", "<?php echo $w['date']; ?>", <?php echo $w['guests']; ?>, "<?php echo $w['status']; ?>")'></i>
                            <i class="fas fa-trash delete-wedding" onclick='deleteWedding(<?php echo $w['id']; ?>)'></i></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- USERS PAGE -->
    <div id="users-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-users"></i> All Users</div>
                <div class="view-all" onclick="showAddUserForm()">+ Add New</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer untuk all_users_data
                        $all_users_data = mysqli_query($conn, "
                            SELECT id, username, email, role, created_at, 'users' as source 
                            FROM users 
                            UNION 
                            SELECT id, username, email, role, created_at, 'admins' as source 
                            FROM admins 
                            ORDER BY id DESC
                        ");
                        while($user = mysqli_fetch_assoc($all_users_data)): ?>
                        <tr class="row-clickable" onclick='showUserDetail(<?php echo json_encode($user); ?>)'>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge <?php echo $user['role'] === 'admin' ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><?php echo isset($user['created_at']) && $user['created_at'] != '0000-00-00 00:00:00' ? date('d M Y', strtotime($user['created_at'])) : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- WEDDINGS PAGE -->
    <div id="weddings-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-ring"></i> All Weddings</div>
                <div class="view-all" onclick="showAddWeddingForm()">+ Create Wedding</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Couple</th><th>Date</th><th>Guests</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="all-weddings-tbody">
                        <?php 
                        $allWeds = mysqli_query($conn, "SELECT * FROM weddings ORDER BY id DESC");
                        while($w = mysqli_fetch_assoc($allWeds)): ?>
                        <tr>
                            <td><?php echo $w['id']; ?></td>
                            <td><?php echo htmlspecialchars($w['couple']); ?></td>
                            <td><?php echo date('d M Y', strtotime($w['date'])); ?></td>
                            <td><?php echo $w['guests']; ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($w['status']); ?>"><?php echo $w['status']; ?></span></td>
                            <td><i class="fas fa-edit edit-wedding" onclick='editWedding(<?php echo $w['id']; ?>, "<?php echo addslashes($w['couple']); ?>", "<?php echo $w['date']; ?>", <?php echo $w['guests']; ?>, "<?php echo $w['status']; ?>")'></i>
                            <i class="fas fa-trash delete-wedding" onclick='deleteWedding(<?php echo $w['id']; ?>)'></i></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- INVITATIONS PAGE -->
    <div id="invitations-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-envelope-open-text"></i> Invitations</div>
                <div class="view-all" onclick="showSendInviteForm()">+ New Invitation</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Guest Name</th><th>Email</th><th>Status</th><th>Sent Date</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $invites = mysqli_query($conn, "SELECT * FROM invitations ORDER BY id DESC");
                        if(mysqli_num_rows($invites) > 0):
                            while($inv = mysqli_fetch_assoc($invites)): ?>
                            <tr>
                                <td><?php echo $inv['id']; ?></td>
                                <td><?php echo htmlspecialchars($inv['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($inv['guest_email']); ?></td>
                                <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                                <td><?php echo isset($inv['sent_date']) && $inv['sent_date'] != '0000-00-00 00:00:00' ? date('d M Y', strtotime($inv['sent_date'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px;">No invitations yet.<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PAYMENTS PAGE -->
    <div id="payments-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-credit-card"></i> Payments</div>
                <div class="view-all" onclick="showPaymentForm()">+ Add Payment</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>User ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pays = mysqli_query($conn, "SELECT * FROM payments ORDER BY id DESC");
                        if(mysqli_num_rows($pays) > 0):
                            while($pay = mysqli_fetch_assoc($pays)): ?>
                            <tr>
                                <td><?php echo $pay['id']; ?></td>
                                <td><?php echo htmlspecialchars($pay['user_id']); ?></td>
                                <td>Rp <?php echo number_format($pay['amount']); ?></td>
                                <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                <td><span class="status-badge status-<?php echo $pay['status']; ?>"><?php echo ucfirst($pay['status']); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px;">No payments yet.<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- REPORTS PAGE -->
    <div id="reports-page" style="display: none;">
        <div class="data-card">
            <div class="section-title"><i class="fas fa-chart-line"></i> Reports & Analytics</div>
            <canvas id="revenueChart" style="max-height: 300px; margin-bottom: 30px;"></canvas>
            <div class="stats-grid">
                <div class="stat-card" onclick="showNotification('Total Revenue: Rp <?php echo number_format($total_payments); ?>')"><div class="stat-info"><h3>Rp <?php echo number_format($total_payments); ?></h3><p>Revenue</p></div></div>
                <div class="stat-card" onclick="showNotification('Total Invitations: <?php echo $total_invitations; ?>')"><div class="stat-info"><h3><?php echo $total_invitations; ?></h3><p>Invitations</p></div></div>
                <div class="stat-card" onclick="showNotification('Total Users: <?php echo $total_users; ?>')"><div class="stat-info"><h3><?php echo $total_users; ?></h3><p>Users</p></div></div>
                <div class="stat-card" onclick="showNotification('Active Weddings: <?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM weddings")); ?>')"><div class="stat-info"><h3><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM weddings")); ?></h3><p>Weddings</p></div></div>
            </div>
            <button class="btn-submit" onclick="showNotification('Report downloaded!')"><i class="fas fa-download"></i> Download Report</button>
        </div>
    </div>

    <!-- USER RATINGS PAGE -->
    <div id="ratings-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-star"></i> User Ratings for Wedding Planners</div>
                <div class="view-all" onclick="showNotification('Total ratings: <?php echo $total_ratings; ?>')">Total: <?php echo $total_ratings; ?> ratings</div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Planner</th><th>User</th><th>Rating</th><th>Stars</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if(isset($all_ratings) && mysqli_num_rows($all_ratings) > 0): ?>
                            <?php while($rating = mysqli_fetch_assoc($all_ratings)): ?>
                            <tr>
                                <td><?php echo $rating['id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <img src="<?php echo htmlspecialchars($rating['photo_url'] ?? 'assets/default-avatar.png'); ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                        <?php echo htmlspecialchars($rating['planner_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($rating['user_name']); ?></td>
                                <td><?php echo $rating['rating']; ?> / 5</td>
                                <td class="rating-stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $rating['rating'] ? '#FFD700' : '#ddd'; ?>;"></i>
                                    <?php endfor; ?>
                                 </td>
                                <td><?php echo date('d M Y, H:i', strtotime($rating['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:40px;">
                                <i class="fas fa-inbox" style="font-size:48px; color:#ccc;"></i><br>
                                No ratings from users yet.<br>
                                <small>Users need to rate wedding planners from their dashboard.</small>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- REQUESTS PAGE -->
    <div id="requests-page" style="display: none;">
        <div class="data-card">
            <div class="table-header">
                <div class="section-title"><i class="fas fa-bell"></i> User Requests</div>
                <div class="view-all" onclick="clearAllRequests()">Clear All</div>
            </div>
            <div id="requestsList" style="margin-top: 20px;"></div>
        </div>
    </div>

    <!-- SETTINGS PAGE -->
    <div id="settings-page" style="display: none;">
        <div class="data-card">
            <div class="section-title"><i class="fas fa-cog"></i> System Settings</div>
            <div class="settings-list" id="settings-list"></div>
        </div>
    </div>
</div>

<div id="detailModal" class="modal" onclick="closeModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<script>
    // ========== VARIABLES ==========
    let settings = {
        general: localStorage.getItem('general_setting') || 'Site Name: Wedding Organizer',
        email: localStorage.getItem('email_setting') || 'SMTP: smtp.gmail.com, Port: 587',
        payment: localStorage.getItem('payment_setting') || 'Currency: IDR, Gateway: Midtrans',
        security: localStorage.getItem('security_setting') || '2FA: Disabled, Session Timeout: 60 min'
    };
    let userRequests = [];

    // ========== PAGE NAVIGATION ==========
    function showPage(page) {
        const pages = ['dashboard', 'users', 'weddings', 'invitations', 'payments', 'reports', 'ratings', 'requests', 'settings'];
        pages.forEach(p => { let el = document.getElementById(p + '-page'); if(el) el.style.display = 'none'; });
        document.getElementById(page + '-page').style.display = 'block';
        document.querySelectorAll('.menu-item').forEach(item => item.classList.remove('active'));
        let menuItem = document.querySelector(`.menu-item[data-page="${page}"]`);
        if(menuItem) menuItem.classList.add('active');
        let titles = {
            dashboard: 'Dashboard', users: 'Users', weddings: 'Weddings', 
            invitations: 'Invitations', payments: 'Payments', reports: 'Reports', 
            ratings: 'User Ratings', requests: 'User Requests', settings: 'Settings'
        };
        let titleEl = document.querySelector('.page-title h1');
        if(titleEl) titleEl.innerText = titles[page];
        if(page === 'settings') loadSettings();
        if(page === 'requests') loadUserRequests();
        if(page === 'reports') loadRevenueChart();
    }

    // ========== WEDDING CRUD ==========
    function editWedding(id, couple, date, guests, status) {
        let html = `<h3>✏️ Edit Wedding</h3>
            <form onsubmit="event.preventDefault(); saveWedding(${id}, this)">
                <div class="form-group"><label>Couple Name</label><input type="text" name="couple" value="${couple.replace(/"/g, '&quot;')}" required></div>
                <div class="form-group"><label>Wedding Date</label><input type="date" name="date" value="${date}" required></div>
                <div class="form-group"><label>Number of Guests</label><input type="number" name="guests" value="${guests}" required></div>
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        <option ${status === 'Planning' ? 'selected' : ''}>Planning</option>
                        <option ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option ${status === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    function saveWedding(id, form) {
        let couple = form.querySelector('[name="couple"]').value;
        let date = form.querySelector('[name="date"]').value;
        let guests = form.querySelector('[name="guests"]').value;
        let status = form.querySelector('[name="status"]').value;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=save_wedding&id=${id}&couple=${encodeURIComponent(couple)}&date=${date}&guests=${guests}&status=${encodeURIComponent(status)}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                showNotification(`✅ Wedding "${couple}" saved!`);
                closeModal();
                location.reload();
            } else {
                showNotification(`❌ Error: ${data.message}`);
            }
        })
        .catch(() => showNotification('❌ Failed to save wedding!'));
    }

    function deleteWedding(id) {
        if(confirm('Are you sure you want to delete this wedding?')) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax_action=delete_wedding&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    showNotification(`✅ Wedding deleted!`);
                    location.reload();
                } else {
                    showNotification(`❌ Error: ${data.message}`);
                }
            })
            .catch(() => showNotification('❌ Failed to delete wedding!'));
        }
    }

    function showAddWeddingForm() {
        let html = `<h3>💍 Create New Wedding</h3>
            <form onsubmit="event.preventDefault(); saveWedding(0, this)">
                <div class="form-group"><label>Couple Name</label><input type="text" name="couple" required></div>
                <div class="form-group"><label>Wedding Date</label><input type="date" name="date" required></div>
                <div class="form-group"><label>Number of Guests</label><input type="number" name="guests" required></div>
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        <option>Planning</option><option>Pending</option><option>Confirmed</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Create Wedding</button>
            </form>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    // ========== SEND INVITATION ==========
    function showSendInviteForm() {
        let html = `<h3>📧 Send Invitation</h3>
            <form onsubmit="event.preventDefault(); sendInvitation(this)">
                <div class="form-group"><label>Guest Name</label><input type="text" name="guest_name" required></div>
                <div class="form-group"><label>Guest Email</label><input type="email" name="guest_email" required></div>
                <div class="form-group"><label>Wedding ID (Optional)</label><input type="text" name="wedding_id"></div>
                <button type="submit" class="btn-submit">Send Invitation</button>
            </form>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    function sendInvitation(form) {
        let guestName = form.querySelector('[name="guest_name"]').value;
        let guestEmail = form.querySelector('[name="guest_email"]').value;
        let weddingId = form.querySelector('[name="wedding_id"]').value;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=save_invitation&guest_name=${encodeURIComponent(guestName)}&guest_email=${encodeURIComponent(guestEmail)}&wedding_id=${encodeURIComponent(weddingId)}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                showNotification(`✅ ${data.message}`);
                closeModal();
                location.reload();
            } else {
                showNotification(`❌ Error: ${data.message}`);
            }
        })
        .catch(() => showNotification('❌ Failed!'));
    }

    // ========== ADD PAYMENT ==========
    function showPaymentForm() {
        let html = `<h3>💰 Record Payment</h3>
            <form onsubmit="event.preventDefault(); addPayment(this)">
                <div class="form-group"><label>User ID</label><input type="text" name="user_id" required></div>
                <div class="form-group"><label>Amount (Rp)</label><input type="number" name="amount" required></div>
                <div class="form-group"><label>Payment Method</label>
                    <select name="payment_method">
                        <option>Bank Transfer</option><option>Credit Card</option><option>PayPal</option><option>Cash</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
                <button type="submit" class="btn-submit">Record Payment</button>
            </form>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }

    function addPayment(form) {
        let userId = form.querySelector('[name="user_id"]').value;
        let amount = form.querySelector('[name="amount"]').value;
        let paymentMethod = form.querySelector('[name="payment_method"]').value;
        let notes = form.querySelector('[name="notes"]').value;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_action=save_payment&user_id=${encodeURIComponent(userId)}&amount=${encodeURIComponent(amount)}&payment_method=${encodeURIComponent(paymentMethod)}&notes=${encodeURIComponent(notes)}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                showNotification(`✅ ${data.message}`);
                closeModal();
                location.reload();
            } else {
                showNotification(`❌ Error: ${data.message}`);
            }
        })
        .catch(() => showNotification('❌ Failed!'));
    }

    // ========== REPORTS ==========
    function loadRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if(!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{ label: 'Revenue (Rp)', data: [25000000, 35000000, 42000000, 38000000, 55000000, 65000000], borderColor: '#D4AF37', backgroundColor: 'rgba(212,175,55,0.1)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
    }

    // ========== USER ==========
    function showAddUserForm() {
        let html = `<h3>➕ Add User</h3>
            <form onsubmit="event.preventDefault(); addUser(this)">
                <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <div class="form-group"><label>Role</label><select name="role"><option value="client">Client</option><option value="user">User</option><option value="admin">Admin</option></select></div>
                <button type="submit" class="btn-submit">Create</button></form>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }
    
    function addUser(form) { 
        let username = form.querySelector('[name="username"]').value; 
        showNotification(`✅ User "${username}" created!`); 
        closeModal(); 
        // Optional: reload to show new user
        setTimeout(() => location.reload(), 1000);
    }
    
    function showUserDetail(user) {
        let createdDate = user.created_at && user.created_at !== '0000-00-00 00:00:00' ? new Date(user.created_at).toLocaleDateString() : 'Unknown';
        let html = `<h3>👤 User Details</h3>
            <div class="detail-item"><strong>ID:</strong> ${user.id}</div>
            <div class="detail-item"><strong>Username:</strong> ${escapeHtml(user.username)}</div>
            <div class="detail-item"><strong>Email:</strong> ${escapeHtml(user.email)}</div>
            <div class="detail-item"><strong>Role:</strong> ${escapeHtml(user.role)}</div>
            <div class="detail-item"><strong>Source:</strong> ${user.source === 'admins' ? 'Admin Table' : 'User Table'}</div>
            <div class="detail-item"><strong>Registered:</strong> ${createdDate}</div>`;
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }
    
    function escapeHtml(str) {
        if(!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            return m;
        });
    }

    // ========== USER REQUESTS ==========
    function loadUserRequests() {
        try { userRequests = JSON.parse(localStorage.getItem('user_requests') || '[]'); } catch(e) { userRequests = []; }
        const container = document.getElementById('requestsList');
        if(!container) return;
        let pendingCount = userRequests.filter(r => r.status === 'pending').length;
        let badge = document.getElementById('requestBadge');
        if(badge) badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
        if(badge && pendingCount > 0) badge.innerText = pendingCount;
        if(userRequests.length === 0) { container.innerHTML = `<div class="empty-state"><i class="fas fa-inbox"></i><p>No requests</p></div>`; return; }
        let html = '';
        userRequests.forEach((req, idx) => {
            let statusClass = req.status === 'pending' ? 'status-pending' : (req.status === 'approved' ? 'status-approved' : 'status-rejected');
            html += `<div class="request-card"><div class="request-header"><span class="request-feature">${escapeHtml(req.feature)}</span><span class="request-user">${escapeHtml(req.user || 'User')}</span><span class="request-date">${new Date(req.timestamp).toLocaleString()}</span></div><div class="request-details">${escapeHtml(req.details || '-')}</div><div class="request-header"><span class="request-status ${statusClass}">${req.status}</span><div class="request-actions">${req.status === 'pending' ? `<button class="btn-approve" onclick="updateRequestStatus(${idx},'approved')">Approve</button><button class="btn-reject" onclick="updateRequestStatus(${idx},'rejected')">Reject</button>` : ''}<button class="btn-delete" onclick="deleteRequest(${idx})">Delete</button></div></div></div>`;
        });
        container.innerHTML = html;
    }
    
    function updateRequestStatus(idx, status) { if(userRequests[idx]) { userRequests[idx].status = status; localStorage.setItem('user_requests', JSON.stringify(userRequests)); loadUserRequests(); showNotification(`✅ ${status}!`); } }
    function deleteRequest(idx) { if(confirm('Delete?')) { userRequests.splice(idx,1); localStorage.setItem('user_requests', JSON.stringify(userRequests)); loadUserRequests(); showNotification('🗑️ Deleted!'); } }
    function clearAllRequests() { if(confirm('Delete all?')) { localStorage.setItem('user_requests', '[]'); loadUserRequests(); showNotification('🗑️ Cleared!'); } }

    // ========== SETTINGS ==========
    function loadSettings() {
        let container = document.getElementById('settings-list');
        if(!container) return;
        container.innerHTML = `<div class="setting-item" onclick="editSetting('general','General Settings',\`${settings.general}\`)"><i class="fas fa-globe"></i><div class="setting-info"><div class="setting-name">General Settings</div><div class="setting-value" id="general_value">${settings.general}</div></div><i class="fas fa-chevron-right"></i></div>
        <div class="setting-item" onclick="editSetting('email','Email Settings',\`${settings.email}\`)"><i class="fas fa-envelope"></i><div class="setting-info"><div class="setting-name">Email Settings</div><div class="setting-value" id="email_value">${settings.email}</div></div><i class="fas fa-chevron-right"></i></div>
        <div class="setting-item" onclick="editSetting('payment','Payment Settings',\`${settings.payment}\`)"><i class="fas fa-credit-card"></i><div class="setting-info"><div class="setting-name">Payment Settings</div><div class="setting-value" id="payment_value">${settings.payment}</div></div><i class="fas fa-chevron-right"></i></div>
        <div class="setting-item" onclick="editSetting('security','Security Settings',\`${settings.security}\`)"><i class="fas fa-shield-alt"></i><div class="setting-info"><div class="setting-name">Security Settings</div><div class="setting-value" id="security_value">${settings.security}</div></div><i class="fas fa-chevron-right"></i></div>`;
    }
    
    function editSetting(key, title, val) { 
        let html = `<h3>⚙️ ${title}</h3><form onsubmit="event.preventDefault(); saveSetting(this,'${key}','${title}')"><div class="form-group"><label>Value</label><textarea name="value" rows="4">${val}</textarea></div><button type="submit" class="btn-submit">Save</button></form>`; 
        document.getElementById('modalContent').innerHTML = html; 
        document.getElementById('detailModal').style.display = 'flex'; 
    }
    
    function saveSetting(form, key, title) { 
        let value = form.querySelector('[name="value"]').value; 
        settings[key] = value; 
        localStorage.setItem(`${key}_setting`, value); 
        let valueEl = document.getElementById(`${key}_value`);
        if(valueEl) valueEl.innerText = value;
        showNotification(`✅ ${title} saved!`); 
        closeModal(); 
    }

    // ========== HELPER ==========
    function showNotification(msg) {
        let toast = document.createElement('div');
        toast.innerHTML = msg;
        toast.style.cssText = `position:fixed; bottom:30px; left:50%; transform:translateX(-50%); background:#1a1a2e; color:white; padding:12px 24px; border-radius:50px; font-size:14px; z-index:1001; animation:fadeInOut 3s; border-left:4px solid #D4AF37;`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    function closeModal() { document.getElementById('detailModal').style.display = 'none'; }

    const style = document.createElement('style');
    style.textContent = `@keyframes fadeInOut { 0% { opacity: 0; transform: translateX(-50%) translateY(20px); } 15% { opacity: 1; transform: translateX(-50%) translateY(0); } 85% { opacity: 1; transform: translateX(-50%) translateY(0); } 100% { opacity: 0; transform: translateX(-50%) translateY(-20px); } }`;
    document.head.appendChild(style);
</script>
</body>
</html>