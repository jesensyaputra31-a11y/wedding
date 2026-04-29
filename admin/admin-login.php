<?php
session_start();
require_once 'php/config.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // PERBEDAAN ADA DI SINI!
    // User login: WHERE username='$username' OR email='$username'
    // Admin login: WHERE username='$username' AND role='admin'  ← tambahan AND role='admin'
    
    $query = "SELECT * FROM users WHERE username='$username' AND role='admin'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username admin tidak ditemukan!";
    }
}
?>
<!-- HTML form login sama seperti index.php -->
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Wedding Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            background: rgba(255,255,255,0.97);
            border-radius: 32px;
            padding: 45px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .crown-icon { text-align: center; margin-bottom: 20px; }
        .crown-icon i { font-size: 55px; color: #D4AF37; }
        .badge {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white;
            padding: 5px 15px;
            border-radius: 40px;
            font-size: 12px;
            display: inline-block;
            margin-top: 10px;
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            text-align: center;
            margin-bottom: 8px;
        }
        .subtitle { text-align: center; font-size: 14px; color: #b48c5c; margin-bottom: 30px; }
        .input-group { margin-bottom: 24px; }
        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #b48c5c;
            margin-bottom: 8px;
        }
        .input-group input {
            width: 100%;
            padding: 14px 0;
            border: none;
            border-bottom: 2px solid #eee;
            font-size: 15px;
            transition: 0.3s;
        }
        .input-group input:focus { outline: none; border-bottom-color: #D4AF37; }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin: 15px 0 20px;
        }
        .login-btn:hover { transform: translateY(-2px); }
        .error-msg {
            background: #fff5f0;
            border-left: 3px solid #e74c3c;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: #c0392b;
            margin-bottom: 20px;
        }
        .separator { text-align: center; margin: 20px 0; position: relative; }
        .separator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #eee;
        }
        .separator span { background: white; padding: 0 15px; position: relative; font-size: 12px; color: #888; }
        .user-link, .back-link { text-align: center; margin-top: 15px; }
        .user-link a, .back-link a { color: #b48c5c; text-decoration: none; font-size: 13px; }
        .user-link a:hover, .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="crown-icon">
            <i class="fas fa-crown"></i>
            <div class="badge">ADMIN PORTAL</div>
        </div>
        <h1>Admin Login</h1>
        <p class="subtitle">Masuk ke panel administrasi</p>

        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>USERNAME / EMAIL</label>
                <input type="text" name="username" placeholder="admin" required>
            </div>
            <div class="input-group">
                <label>PASSWORD</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>

        <div class="separator"><span>atau</span></div>
        <div class="user-link"><a href="index.php"><i class="fas fa-user"></i> Login sebagai User Biasa</a></div>
        <div class="back-link"><a href="index.php">← Kembali ke Beranda</a></div>
    </div>
</body>
</html>