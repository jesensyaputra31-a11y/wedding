<?php
session_start();
require_once 'php/config.php';

// Cek apakah sudah login sebagai admin
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin-dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Cek di tabel admins
    $query = "SELECT * FROM admins WHERE username = '$username' OR email = '$username'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        if(password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Password salah";
        }
    } else {
        $error = "Username atau Email tidak ditemukan";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer — Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Background Video */
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            filter: brightness(0.45) contrast(1.1);
        }

        /* Overlay Gradien */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(0,0,0,0.6) 0%, 
                rgba(60,30,45,0.5) 50%,
                rgba(0,0,0,0.6) 100%);
            z-index: 1;
        }

        /* ========== ELEMEN BUNGA ========== */
        .flower {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            opacity: 0.6;
        }
        .flower-1 { top: 5%; left: 3%; width: 120px; animation: floatFlower 8s ease-in-out infinite; }
        .flower-2 { top: 8%; right: 4%; width: 100px; animation: floatFlower 10s ease-in-out infinite reverse; }
        .flower-3 { bottom: 8%; left: 5%; width: 90px; animation: floatFlower 7s ease-in-out infinite 1s; }
        .flower-4 { bottom: 12%; right: 3%; width: 110px; animation: floatFlower 9s ease-in-out infinite 2s; }
        .flower-5 { top: 40%; left: 8%; width: 50px; animation: floatFlower 6s ease-in-out infinite 0.5s; }
        .flower-6 { bottom: 25%; right: 7%; width: 60px; animation: floatFlower 7s ease-in-out infinite 1.5s; }

        @keyframes floatFlower {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.4; }
            50% { transform: translateY(-20px) rotate(5deg); opacity: 0.8; }
        }

        /* Falling Petals */
        .petal {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            font-size: 18px;
            opacity: 0.5;
            animation: fall linear infinite;
        }
        @keyframes fall {
            from { transform: translateY(-100vh) rotate(0deg); opacity: 0.7; }
            to { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }

        /* Container */
        .container {
            position: relative;
            z-index: 3;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            padding: 0 60px;
            gap: 60px;
        }

        /* ========== PANEL KIRI ========== */
        .left-panel {
            flex: 1;
            color: white;
            animation: fadeInLeft 0.8s ease-out;
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .logo {
            margin-bottom: 30px;
        }
        .logo h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #FFD700, #F5A623);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .logo p {
            font-size: 11px;
            letter-spacing: 4px;
            color: rgba(255,215,0,0.7);
            margin-top: 5px;
        }

        .quote-section {
            margin: 40px 0;
        }
        .quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 500;
            line-height: 1.3;
            margin-bottom: 20px;
            position: relative;
        }
        .quote::before {
            content: '“';
            font-size: 80px;
            position: absolute;
            left: -30px;
            top: -20px;
            opacity: 0.4;
            font-family: serif;
        }
        .quote-author {
            font-size: 14px;
            opacity: 0.7;
            letter-spacing: 2px;
        }

        .verse {
            margin-top: 30px;
            padding: 20px;
            border-left: 2px solid #FFD700;
        }
        .verse p {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.85;
            margin-bottom: 8px;
        }
        .verse .small {
            font-size: 11px;
            opacity: 0.6;
        }

        /* ========== PANEL KANAN - FORM LOGIN ========== */
        .right-panel {
            flex: 0.8;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 32px;
            padding: 45px 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInRight 0.8s ease-out;
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .right-panel h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .right-panel .sub {
            font-size: 13px;
            color: #888;
            margin-bottom: 30px;
            border-left: 3px solid #FFD700;
            padding-left: 12px;
        }

        .input-group {
            margin-bottom: 22px;
        }
        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b48c5c;
            margin-bottom: 8px;
        }
        .input-group input {
            width: 100%;
            padding: 12px 0;
            border: none;
            border-bottom: 1.5px solid #e0e0e0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: 0.2s;
            background: transparent;
        }
        .input-group input:focus {
            outline: none;
            border-bottom-color: #FFD700;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 22px 0 25px;
            font-size: 12px;
        }
        .checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #666;
        }
        .checkbox input {
            accent-color: #FFD700;
        }
        .forgot-link {
            color: #b48c5c;
            text-decoration: none;
        }
        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #b48c5c, #8b6842);
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(180,140,92,0.3);
        }

        .error-msg {
            background: #fff5f0;
            border-left: 3px solid #e74c3c;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            color: #c0392b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .register-link {
            text-align: center;
            font-size: 12px;
            color: #888;
            padding-top: 18px;
            border-top: 1px solid #f0f0f0;
        }
        .register-link a {
            color: #b48c5c;
            text-decoration: none;
            font-weight: 600;
        }

        .user-link {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
        .user-link a {
            color: #b48c5c;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .content {
                flex-direction: column;
                justify-content: center;
                padding: 40px;
                gap: 30px;
            }
            .left-panel {
                text-align: center;
            }
            .quote::before {
                left: -20px;
            }
            .verse {
                text-align: left;
            }
            .right-panel {
                width: 100%;
                max-width: 450px;
            }
            .flower-1, .flower-2, .flower-3, .flower-4 {
                width: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <video class="bg-video" autoplay muted loop playsinline>
        <source src="https://assets.mixkit.co/videos/preview/mixkit-wedding-couple-hands-and-bouquet-39874-large.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <!-- Elemen Bunga -->
    <svg class="flower flower-1" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#FFD700" opacity="0.8"/>
        <circle cx="50" cy="50" r="12" fill="#FFA500"/>
        <circle cx="50" cy="35" r="4" fill="#FFF"/>
        <circle cx="65" cy="50" r="4" fill="#FFF"/>
        <circle cx="50" cy="65" r="4" fill="#FFF"/>
        <circle cx="35" cy="50" r="4" fill="#FFF"/>
    </svg>
    <svg class="flower flower-2" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#F48FB1" opacity="0.7"/>
        <circle cx="50" cy="50" r="12" fill="#E91E63"/>
    </svg>
    <svg class="flower flower-3" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#FFD700" opacity="0.6"/>
        <circle cx="50" cy="50" r="10" fill="#F5A623"/>
    </svg>
    <svg class="flower flower-4" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#F48FB1" opacity="0.7"/>
        <circle cx="50" cy="50" r="12" fill="#EC407A"/>
    </svg>
    <svg class="flower flower-5" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#FFD700" opacity="0.5"/>
        <circle cx="50" cy="50" r="8" fill="#FFA500"/>
    </svg>
    <svg class="flower flower-6" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#F48FB1" opacity="0.5"/>
        <circle cx="50" cy="50" r="8" fill="#E91E63"/>
    </svg>

    <div id="petals-container"></div>

    <div class="container">
        <div class="content">
            <!-- LEFT PANEL - Kata-kata Aesthetic -->
            <div class="left-panel">
                <div class="logo">
                    <h1>Wedding<br>Organizer</h1>
                    <p>ADMIN PORTAL</p>
                </div>

                <div class="quote-section">
                    <div class="quote">
                        Welcome to the<br>Administrator<br>Portal
                    </div>
                    <div class="quote-author">— Wedding Organizer Team</div>
                </div>

                <div class="verse">
                    <p>✦ "Manage, monitor, and create perfect weddings." ✦</p>
                    <p class="small">— Administrator Access Only</p>
                </div>
            </div>

            <!-- RIGHT PANEL - Form Login Admin -->
            <div class="right-panel">
                <h2>Admin Access</h2>
                <div class="sub">Login to manage wedding platform</div>

                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label>USERNAME / EMAIL</label>
                        <input type="text" name="username" placeholder="admin@wedding.com" required>
                    </div>

                    <div class="input-group">
                        <label>PASSWORD</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember"> Remember Me
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">
                        <i class="fas fa-arrow-right"></i> SIGN IN
                    </button>

                    <div class="user-link">
                        <a href="index.php">
                            <i class="fas fa-user"></i> Login as Regular User
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function createPetal() {
            const petals = ['🌸', '🌹', '🌺', '🌼', '🌸', '🌷', '🥀'];
            const petal = document.createElement('div');
            petal.className = 'petal';
            petal.innerHTML = petals[Math.floor(Math.random() * petals.length)];
            petal.style.left = Math.random() * 100 + '%';
            petal.style.animationDuration = Math.random() * 5 + 4 + 's';
            petal.style.animationDelay = Math.random() * 5 + 's';
            petal.style.fontSize = Math.random() * 16 + 12 + 'px';
            document.body.appendChild(petal);
            setTimeout(() => petal.remove(), 10000);
        }
        setInterval(createPetal, 800);
    </script>
</body>
</html>