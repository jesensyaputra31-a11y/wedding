<?php
session_start(); // <-- INI PENTING! Harus ada di paling atas
require_once 'php/config.php';

$error = '';
$success = '';
$step = 1;

// Cek session untuk menentukan step
if(isset($_SESSION['reset_step']) && $_SESSION['reset_step'] == 2) {
    $step = 2;
}
if(isset($_SESSION['reset_step']) && $_SESSION['reset_step'] == 3) {
    $step = 3;
}

// Cek parameter URL
if(isset($_GET['step'])) {
    if($_GET['step'] == 1) {
        $step = 1;
        unset($_SESSION['reset_step']);
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_email']);
    } elseif($_GET['step'] == 2 && isset($_SESSION['reset_token'])) {
        $step = 2;
    } elseif($_GET['step'] == 3) {
        $step = 3;
    }
}

$check_token = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token'");
if(mysqli_num_rows($check_token) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
}

if(isset($_POST['request_reset'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = "SELECT id, username, full_name FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $update_query = "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE email = '$email'";
        
        if(mysqli_query($conn, $update_query)) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_step'] = 2;
            $success = "Reset code has been generated.";
            $step = 2;
            
            // Redirect ke step 2
            header("Location: forgot-password.php?step=2");
            exit();
        } else {
            $error = "Failed to generate token.";
        }
    } else {
        $error = "Email not found in our database!";
    }
}

if(isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    
    if($new_password != $confirm_password) {
        $error = "Password and confirmation do not match!";
    } elseif(strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $query = "SELECT email FROM users WHERE reset_token = '$token' AND reset_expires > NOW()";
        $result = mysqli_query($conn, $query);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE email = '{$user['email']}'";
            
            if(mysqli_query($conn, $update_query)) {
                $success = "Password successfully reset! Please login with your new password.";
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_step']);
                $step = 3;
                
                header("Location: forgot-password.php?step=3");
                exit();
            } else {
                $error = "Failed to reset password!";
            }
        } else {
            $error = "Invalid or expired token! Please start over.";
            $step = 1;
            unset($_SESSION['reset_step']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Reset Password - Wedding Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
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
            overflow-y: auto; /* Bisa scroll ke bawah */
            overflow-x: hidden;
        }

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

        .container {
            position: relative;
            z-index: 3;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
        }

        /* CARD DENGAN EFEK SWIPE & SCROLL */
        .reset-card {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(15px);
            border-radius: 48px;
            padding: 45px 40px;
            box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.4), 
                        0 0 0 1px rgba(212, 175, 55, 0.2),
                        inset 0 1px 0 rgba(255,255,255,0.3);
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            will-change: transform;
            cursor: grab;
            animation: fadeInUp 0.6s ease-out;
            /* Penting untuk swipe di HP */
            touch-action: pan-y pinch-zoom; /* Izinkan scroll vertikal */
        }

        .reset-card:active {
            cursor: grabbing;
        }

        .reset-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 40px 70px -25px rgba(0, 0, 0, 0.5), 
                        0 0 0 1px rgba(212, 175, 55, 0.3);
        }

        /* Animasi swipe */
        @keyframes cardSwipeLeft {
            0% { transform: translateX(0) rotate(0deg); opacity: 1; }
            30% { transform: translateX(-30px) rotate(-5deg); }
            100% { transform: translateX(-400px) rotate(-15deg); opacity: 0; }
        }

        @keyframes cardSwipeRight {
            0% { transform: translateX(0) rotate(0deg); opacity: 1; }
            30% { transform: translateX(30px) rotate(5deg); }
            100% { transform: translateX(400px) rotate(15deg); opacity: 0; }
        }

        .card-swipe-left {
            animation: cardSwipeLeft 0.5s ease forwards !important;
        }

        .card-swipe-right {
            animation: cardSwipeRight 0.5s ease forwards !important;
        }

        /* Swipe indicator */
        .swipe-indicator {
            text-align: center;
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            z-index: 10;
            opacity: 0.7;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .swipe-indicator span {
            display: inline-block;
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            background: rgba(0,0,0,0.4);
            padding: 8px 16px;
            border-radius: 40px;
            backdrop-filter: blur(5px);
        }

        .swipe-indicator i {
            margin: 0 4px;
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-wrapper {
            text-align: center;
            margin-bottom: 25px;
        }

        .icon-wrapper i {
            font-size: 52px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: iconPulse 2s ease infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .reset-card h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            font-size: 14px;
            color: #888;
            margin-bottom: 35px;
        }

        .input-group {
            margin-bottom: 24px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #b48c5c;
            margin-bottom: 8px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 0;
            border: none;
            border-bottom: 2px solid #eee;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: transparent;
        }

        .input-group input:focus {
            outline: none;
            border-bottom-color: #D4AF37;
        }

        .input-group::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #D4AF37, #B8860B);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .input-group:focus-within::after {
            width: 100%;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 0 20px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-msg, .success-msg {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-msg {
            background: rgba(231, 76, 60, 0.1);
            border-left: 3px solid #e74c3c;
            color: #c0392b;
        }

        .success-msg {
            background: rgba(46, 125, 50, 0.1);
            border-left: 3px solid #2e7d32;
            color: #2e7d32;
        }

        .token-card {
            background: linear-gradient(135deg, #f8f6f3, #fff);
            padding: 20px;
            border-radius: 24px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid rgba(212,175,55,0.2);
            transition: all 0.3s;
        }

        .token-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .token-code {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            color: #D4AF37;
            background: white;
            padding: 14px;
            border-radius: 16px;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #eee;
        }

        .copy-btn {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            color: white;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            transform: scale(1.05);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }

        .back-link a {
            color: #b48c5c;
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #D4AF37;
        }

        .success-btn {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 50px;
            margin-top: 15px;
            width: 100%;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }

        .success-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35);
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #D4AF37;
            border-radius: 10px;
        }

        /* Responsif HP */
        @media (max-width: 550px) {
            .container {
                padding: 60px 16px;
                align-items: flex-start;
            }
            
            .reset-card {
                padding: 35px 25px;
                border-radius: 36px;
                margin-top: 20px;
            }
            
            .reset-card h1 {
                font-size: 26px;
            }
            
            .icon-wrapper i {
                font-size: 44px;
            }
            
            .input-group input {
                padding: 12px 0;
                font-size: 14px;
            }
            
            .btn, .success-btn {
                padding: 12px;
            }
            
            .token-card {
                padding: 15px;
            }
            
            .token-code {
                font-size: 11px;
                padding: 10px;
            }
        }

        @media (max-width: 380px) {
            .reset-card {
                padding: 25px 20px;
            }
            
            .reset-card h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 12px;
            }
        }

        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            right: 20px;
            margin-top: -8px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <video class="bg-video" autoplay muted loop playsinline>
        <source src="https://assets.mixkit.co/videos/preview/mixkit-wedding-couple-hands-and-bouquet-39874-large.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <svg class="flower flower-1" viewBox="0 0 100 100">
        <path d="M50,20 Q65,35 80,50 Q65,65 50,80 Q35,65 20,50 Q35,35 50,20Z" fill="#FFD700" opacity="0.8"/>
        <circle cx="50" cy="50" r="12" fill="#FFA500"/>
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
        <div class="reset-card" id="resetCard">
            <div class="icon-wrapper">
                <i class="fas fa-key"></i>
            </div>

            <?php if($step == 1): ?>
                <h1>Forgot Password?</h1>
                <p class="subtitle">Enter your email to reset your password</p>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" id="resetForm">
                    <div class="input-group">
                        <label>EMAIL ADDRESS</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                    <button type="submit" name="request_reset" class="btn" id="submitBtn">Send Reset Code</button>
                    <div class="back-link"><a href="index.php">← Back to Login</a></div>
                </form>
                
            <?php elseif($step == 2): ?>
                <h1>Reset Password</h1>
                <p class="subtitle">Enter the token and your new password</p>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="token-card">
                    <strong>📋 Your Reset Token:</strong>
                    <div class="token-code" id="tokenCode"><?php echo isset($_SESSION['reset_token']) ? $_SESSION['reset_token'] : ''; ?></div>
                    <button type="button" onclick="copyToken()" class="copy-btn"><i class="fas fa-copy"></i> Copy Token</button>
                </div>
                
                <div class="info-msg" style="background: #e8f4fd; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 12px; color: #2196F3;">
                    <i class="fas fa-info-circle"></i> Use the token above to reset your password. Token expires in 1 hour.
                </div>
                
                <form method="POST" id="resetForm2">
                    <div class="input-group">
                        <label>RESET TOKEN</label>
                        <input type="text" name="token" placeholder="Paste or type the reset token" required>
                    </div>
                    <div class="input-group">
                        <label>NEW PASSWORD</label>
                        <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="input-group">
                        <label>CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn" id="submitBtn2">Reset Password</button>
                    <div class="back-link">
                        <a href="forgot-password.php?step=1">← Start Over</a> | 
                        <a href="index.php">Back to Login</a>
                    </div>
                </form>
                
            <?php elseif($step == 3): ?>
                <h1>Success! 🎉</h1>
                <p class="subtitle">Your password has been reset successfully</p>
                
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                
                <div style="text-align: center; margin: 20px 0;">
                    <i class="fas fa-lock-open" style="font-size: 48px; color: #2ecc71;"></i>
                </div>
                
                <a href="index.php" class="success-btn"><i class="fas fa-sign-in-alt"></i> Login Now →</a>
                
                <div class="back-link" style="margin-top: 20px;">
                    <a href="index.php">← Back to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="swipe-indicator" id="swipeIndicator">
        <span><i class="fas fa-hand-peace"></i> Geser card ke kiri/kanan untuk efek <i class="fas fa-arrow-right"></i></span>
    </div>

    <script>
        // Create falling petals
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

        // Copy token function
        function copyToken() {
            var token = document.getElementById("tokenCode");
            if(token) {
                var tokenText = token.innerText;
                navigator.clipboard.writeText(tokenText).then(function() {
                    showToast("✅ Token copied successfully!");
                }).catch(function() {
                    alert("Manual copy: " + tokenText);
                });
            }
        }

        // Toast notification
        function showToast(message) {
            let toast = document.createElement('div');
            toast.innerHTML = message;
            toast.style.cssText = `position:fixed; bottom:100px; left:50%; transform:translateX(-50%); background:#1a1a2e; color:white; padding:12px 24px; border-radius:50px; font-size:14px; z-index:1001; animation:fadeInOut 2s ease; border-left:4px solid #D4AF37; white-space:nowrap;`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }

        // ========== EFEK SWIPE PADA CARD (Hanya horizontal, tidak mengganggu scroll vertikal) ==========
        const card = document.getElementById('resetCard');
        const swipeIndicator = document.getElementById('swipeIndicator');
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        let dragStartTime = 0;

        if (card) {
            // Touch events untuk HP (swipe horizontal saja)
            card.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                dragStartTime = Date.now();
                isDragging = true;
                card.style.transition = 'none';
                if(swipeIndicator) swipeIndicator.style.opacity = '0.3';
            });

            card.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
                const diffX = currentX - startX;
                
                // Hanya swipe horizontal, tidak mengganggu scroll vertikal
                if (Math.abs(diffX) > 10) {
                    e.preventDefault(); // Prevent scroll only when swiping horizontally
                    const rotate = diffX * 0.03;
                    const opacity = 1 - Math.abs(diffX) / 300;
                    card.style.transform = `translateX(${diffX}px) rotate(${rotate}deg)`;
                    card.style.opacity = Math.max(0.5, opacity);
                }
            });

            card.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                isDragging = false;
                const diffX = currentX - startX;
                const dragDuration = Date.now() - dragStartTime;
                
                card.style.transition = 'all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1)';
                
                // Deteksi swipe yang cukup jauh atau cepat
                if (Math.abs(diffX) > 80 || (Math.abs(diffX) > 40 && dragDuration < 200)) {
                    if (diffX > 0) {
                        card.classList.add('card-swipe-right');
                        showToast("👉 Swipe right!");
                    } else {
                        card.classList.add('card-swipe-left');
                        showToast("👈 Swipe left!");
                    }
                    
                    setTimeout(() => {
                        card.style.transform = '';
                        card.style.opacity = '';
                        card.classList.remove('card-swipe-left', 'card-swipe-right');
                    }, 500);
                } else {
                    card.style.transform = '';
                    card.style.opacity = '';
                }
                
                setTimeout(() => {
                    if(swipeIndicator) swipeIndicator.style.opacity = '0.7';
                }, 500);
            });
            
            // Mouse events untuk desktop
            card.addEventListener('mousedown', (e) => {
                startX = e.clientX;
                isDragging = true;
                card.style.transition = 'none';
                card.style.cursor = 'grabbing';
            });
            
            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                currentX = e.clientX;
                const diffX = currentX - startX;
                
                if (Math.abs(diffX) > 10) {
                    const rotate = diffX * 0.02;
                    card.style.transform = `translateX(${diffX}px) rotate(${rotate}deg)`;
                    card.style.opacity = 1 - Math.abs(diffX) / 300;
                }
            });
            
            window.addEventListener('mouseup', (e) => {
                if (!isDragging) return;
                isDragging = false;
                const diffX = currentX - startX;
                card.style.transition = 'all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1)';
                card.style.cursor = 'grab';
                
                if (Math.abs(diffX) > 80) {
                    if (diffX > 0) {
                        card.classList.add('card-swipe-right');
                        showToast("👉 Swipe right!");
                    } else {
                        card.classList.add('card-swipe-left');
                        showToast("👈 Swipe left!");
                    }
                    setTimeout(() => {
                        card.classList.remove('card-swipe-left', 'card-swipe-right');
                    }, 500);
                }
                card.style.transform = '';
                card.style.opacity = '';
            });
            
            card.style.cursor = 'grab';
        }

        // Loading effect on form submit
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('#submitBtn, #submitBtn2, .btn');
                if (btn && !btn.classList.contains('btn-loading')) {
                    btn.classList.add('btn-loading');
                    btn.disabled = true;
                }
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInOut {
                0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
                15% { opacity: 1; transform: translateX(-50%) translateY(0); }
                85% { opacity: 1; transform: translateX(-50%) translateY(0); }
                100% { opacity: 0; transform: translateX(-50%) translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
        
        // Tampilkan notifikasi token jika di step 2
        <?php if($step == 2 && isset($_SESSION['reset_token'])): ?>
        setTimeout(function() {
            showToast("📋 Token has been generated! Copy and use it.");
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>