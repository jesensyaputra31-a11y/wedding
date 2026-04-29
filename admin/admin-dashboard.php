<?php
session_start();
require_once 'php/config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit();
}

$adminName = $_SESSION['full_name'] ?? $_SESSION['username'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'"))['count'];
$total_users_only = $total_users - $total_admins;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f0eb; }
        nav {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .logo { font-size: 24px; font-weight: 600; color: #D4AF37; }
        .admin-badge { background: #D4AF37; color: white; padding: 8px 20px; border-radius: 40px; margin-right: 10px; }
        .nav-links { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .btn-link {
            background: #b48c5c;
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-link i { margin-right: 5px; }
        .logout-btn { background: #e74c3c; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .welcome {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            padding: 40px;
            border-radius: 24px;
            color: white;
            margin-bottom: 40px;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 20px; text-align: center; }
        .stat-card i { font-size: 45px; color: #D4AF37; margin-bottom: 15px; }
        .stat-card .number { font-size: 36px; font-weight: 700; color: #1a1a2e; }
        .card { background: white; border-radius: 20px; padding: 25px; }
        .card-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; border-bottom: 2px solid #f0e5dc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #D4AF37; }
        .role-badge { background: #D4AF37; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; }
        @media (max-width: 700px) {
            .stats-grid { grid-template-columns: 1fr; }
            nav { flex-direction: column; gap: 15px; }
            .nav-links { flex-direction: column; width: 100%; }
            .btn-link { text-align: center; width: 100%; }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">Wedding Organizer | Admin Panel</div>
        <div class="nav-links">
            <span class="admin-badge"><i class="fas fa-crown"></i> <?php echo $adminName; ?></span>
            <a href="admin-change-password.php" class="btn-link"><i class="fas fa-key"></i> Ubah Password</a>
            <a href="logout.php" class="btn-link logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <div class="container">
        <div class="welcome">
            <h1>Selamat Datang, Admin <?php echo $adminName; ?>! 👑</h1>
            <p>Kelola platform wedding, pengguna, dan pantau semua aktivitas.</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><i class="fas fa-users"></i><div class="number"><?php echo $total_users; ?></div><div class="label">Total Pengguna</div></div>
            <div class="stat-card"><i class="fas fa-user-shield"></i><div class="number"><?php echo $total_admins; ?></div><div class="label">Total Admin</div></div>
            <div class="stat-card"><i class="fas fa-user"></i><div class="number"><?php echo $total_users_only; ?></div><div class="label">Pengguna Biasa</div></div>
        </div>
        <div class="card">
            <div class="card-title"><i class="fas fa-users"></i> Daftar Pengguna Terdaftar</div>
            <table>
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>
                </thead>
                <tbody>
                    <?php
                    $users_query = mysqli_query($conn, "SELECT id, username, email, role FROM users ORDER BY id DESC");
                    while($user = mysqli_fetch_assoc($users_query)) {
                        echo "<tr>";
                        echo "<td>{$user['id']}</td>";
                        echo "<td>{$user['username']}</td>";
                        echo "<td>{$user['email']}</td>";
                        echo "<td><span class='role-badge'>{$user['role']}</span></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>