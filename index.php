<?php
require_once 'php/config.php';

// Jika sudah login, langsung ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username='$username' OR email='$username'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: dashboard.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Wedding Organizer — Login</title>
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
            overflow-x: hidden;
        }

        /* Background Video - Optimasi untuk HP */
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

        /* ========== ELEMEN BUNGA & DECORATIONS - UKURAN HP ========== */
        .flower {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            opacity: 0.6;
        }

        /* Ukuran normal untuk desktop, akan mengecil di HP */
        .flower-1 { top: 5%; left: 3%; width: 120px; animation: floatFlower 8s ease-in-out infinite; }
        .flower-2 { top: 8%; right: 4%; width: 100px; animation: floatFlower 10s ease-in-out infinite reverse; }
        .flower-3 { bottom: 8%; left: 5%; width: 90px; animation: floatFlower 7s ease-in-out infinite 1s; }
        .flower-4 { bottom: 12%; right: 3%; width: 110px; animation: floatFlower 9s ease-in-out infinite 2s; }
        .flower-5 { top: 40%; left: 8%; width: 50px; animation: floatFlower 6s ease-in-out infinite 0.5s; }
        .flower-6 { bottom: 25%; right: 7%; width: 60px; animation: floatFlower 7s ease-in-out infinite 1.5s; }

        @keyframes floatFlower {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.4; }
            50% { transform: translateY(-15px) rotate(5deg); opacity: 0.8; }
        }

        /* Petals Jatuh */
        .petal {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            font-size: 16px;
            opacity: 0.5;
            animation: fall linear infinite;
        }

        @keyframes fall {
            from { transform: translateY(-100vh) rotate(0deg); opacity: 0.7; }
            to { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }

        /* ========== MAIN CONTAINER ========== */
        .container {
            position: relative;
            z-index: 3;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Konten - Default Desktop 2 kolom, HP jadi 1 kolom */
        .content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
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
            font-size: 32px;
            font-weight: 500;
            line-height: 1.3;
            margin-bottom: 20px;
            position: relative;
        }

        .quote::before {
            content: '“';
            font-size: 60px;
            position: absolute;
            left: -25px;
            top: -15px;
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
            padding: 15px;
            border-left: 2px solid #FFD700;
        }

        .verse p {
            font-size: 13px;
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
            background: rgba(255, 255, 255, 0.96);
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
            font-size: 14px;
            color: #888;
            margin-bottom: 30px;
            border-left: 3px solid #FFD700;
            padding-left: 12px;
        }

        /* Input Fields */
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
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: 0.2s;
            background: transparent;
        }

        .input-group input:focus {
            outline: none;
            border-bottom-color: #FFD700;
        }

        /* Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 22px 0 25px;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 10px;
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
            width: 16px;
            height: 16px;
        }

        .forgot-link {
            color: #b48c5c;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #b48c5c, #8b6842);
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(180,140,92,0.3);
        }

        /* Error Message */
        .error-msg {
            background: #fff5f0;
            border-left: 3px solid #e74c3c;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            color: #c0392b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Register Link */
        .register-link {
            text-align: center;
            font-size: 13px;
            color: #888;
            padding-top: 18px;
            border-top: 1px solid #f0f0f0;
        }

        .register-link a {
            color: #b48c5c;
            text-decoration: none;
            font-weight: 600;
        }

        /* Admin Link */
        .admin-link {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
        .admin-link a {
            color: #b48c5c;
            text-decoration: none;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        .admin-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        /* ========== RESPONSIVE UNTUK HP (Mobile) ========== */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                min-height: 100vh;
                align-items: center;
            }
            
            /* Ubah dari 2 kolom jadi 1 kolom */
            .content {
                flex-direction: column;
                padding: 0;
                gap: 25px;
            }
            
            /* Panel Kiri - lebih compact */
            .left-panel {
                text-align: center;
                width: 100%;
                padding: 0 10px;
            }
            
            .logo h1 {
                font-size: 36px;
            }
            
            .logo p {
                font-size: 9px;
                letter-spacing: 3px;
            }
            
            .quote {
                font-size: 22px;
            }
            
            .quote::before {
                font-size: 45px;
                left: -15px;
                top: -10px;
            }
            
            .quote-author {
                font-size: 12px;
            }
            
            .verse {
                padding: 12px;
                text-align: left;
                display: inline-block;
                text-align: center;
                width: 100%;
            }
            
            .verse p {
                font-size: 12px;
            }
            
            /* Panel Kanan - form lebih nyaman di HP */
            .right-panel {
                width: 100%;
                max-width: 100%;
                padding: 30px 25px;
                border-radius: 28px;
            }
            
            .right-panel h2 {
                font-size: 28px;
                text-align: center;
            }
            
            .right-panel .sub {
                font-size: 13px;
                text-align: center;
                border-left: none;
                padding-left: 0;
                margin-bottom: 25px;
            }
            
            .input-group {
                margin-bottom: 20px;
            }
            
            .input-group input {
                font-size: 14px;
                padding: 10px 0;
            }
            
            .form-options {
                margin: 18px 0 20px;
                font-size: 12px;
            }
            
            .login-btn {
                padding: 13px;
                font-size: 13px;
            }
            
            .error-msg {
                padding: 10px 12px;
                font-size: 12px;
            }
            
            .register-link, .admin-link {
                font-size: 12px;
            }
            
            /* Bunga - perkecil ukuran di HP */
            .flower-1, .flower-2, .flower-3, .flower-4 {
                width: 60px;
            }
            .flower-5, .flower-6 {
                width: 35px;
            }
            
            /* Petal lebih kecil */
            .petal {
                font-size: 12px;
            }
        }

        /* Untuk HP sangat kecil (max width 480px) */
        @media (max-width: 480px) {
            .right-panel {
                padding: 25px 20px;
            }
            
            .right-panel h2 {
                font-size: 24px;
            }
            
            .logo h1 {
                font-size: 32px;
            }
            
            .quote {
                font-size: 18px;
            }
            
            .quote::before {
                font-size: 35px;
                left: -10px;
                top: -8px;
            }
            
            .verse p {
                font-size: 11px;
            }
            
            .input-group label {
                font-size: 10px;
            }
            
            .input-group input {
                font-size: 13px;
            }
            
            .login-btn {
                padding: 12px;
                font-size: 12px;
            }
            
            .flower-1, .flower-2, .flower-3, .flower-4 {
                width: 45px;
            }
        }

        /* Landscape mode di HP */
        @media (max-width: 900px) and (orientation: landscape) {
            .container {
                min-height: auto;
                padding: 20px;
            }
            
            .content {
                gap: 20px;
            }
            
            .left-panel {
                margin-bottom: 0;
            }
            
            .quote {
                font-size: 18px;
            }
            
            .logo h1 {
                font-size: 28px;
            }
            
            .verse {
                margin-top: 10px;
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

    <!-- Elemen Bunga Aesthetic -->
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
                    <p>ELEGANT WEDDING SUITE</p>
                </div>

                <div class="quote-section">
                    <div class="quote">
                        Every love story is beautiful,<br>but yours deserves to be<br>perfectly crafted.
                    </div>
                    <div class="quote-author">— Tim Wedding Organizer</div>
                </div>

                <div class="verse">
                    <p>✦ "Where two hearts become one,<br>and dreams become reality." ✦</p>
                    <p class="small">— Celebrate your love story with us</p>
                </div>
            </div>

            <!-- RIGHT PANEL - Form Login -->
            <div class="right-panel">
                <h2>Welcome Back</h2>
                <div class="sub">Login to continue your journey</div>

                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label>EMAIL / USERNAME</label>
                        <input type="text" name="username" placeholder="your@email.com" required>
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

                    <div class="register-link">
                        Don't have an account? <a href="register.php">Create Account →</a>
                    </div>

                    <div class="admin-link">
                        <a href="admin-login.php">
                            <i class="fas fa-crown"></i> Admin Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Falling Petals Effect
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
            
            setTimeout(() => {
                petal.remove();
            }, 10000);
        }
        
        setInterval(createPetal, 800);
    </script>
</body>
</html>