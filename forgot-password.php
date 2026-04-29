<?php
require_once 'php/config.php';

$error = '';
$success = '';
$step = 1;

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
            $success = "✨ Reset code has been generated. ✨";
            $step = 2;
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
                $success = "🎉 Password successfully reset! 🎉";
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                $step = 3;
            } else {
                $error = "Failed to reset password!";
            }
        } else {
            $error = "Invalid or expired token! Please start over.";
            $step = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            overflow-x: hidden;
        }

        /* Video Background */
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            filter: brightness(0.5) contrast(1.1);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(0,0,0,0.65) 0%, 
                rgba(60,30,45,0.5) 50%,
                rgba(0,0,0,0.65) 100%);
            z-index: 1;
        }

        /* ========== BUNGA BESAR DI SAMPING ========== */
        .flower-large {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            opacity: 0.5;
        }
        .flower-left {
            bottom: 5%;
            left: 2%;
            width: 180px;
            animation: floatSlow 12s ease-in-out infinite;
        }
        .flower-right {
            top: 5%;
            right: 2%;
            width: 150px;
            animation: floatSlow 10s ease-in-out infinite reverse;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }

        /* Bunga kecil dekoratif */
        .flower-small {
            position: fixed;
            z-index: 2;
            pointer-events: none;
            opacity: 0.4;
        }
        .fs-1 { top: 15%; left: 8%; width: 50px; animation: floatFlower 6s infinite; }
        .fs-2 { top: 70%; right: 10%; width: 60px; animation: floatFlower 7s infinite 1s; }
        .fs-3 { bottom: 20%; left: 12%; width: 40px; animation: floatFlower 5s infinite 2s; }
        .fs-4 { top: 40%; right: 5%; width: 45px; animation: floatFlower 8s infinite 0.5s; }

        @keyframes floatFlower {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
            50% { transform: translateY(-15px) rotate(5deg); opacity: 0.7; }
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        /* MAIN CARD */
        .reset-card {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 45px 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(212, 175, 55, 0.2);
            animation: fadeInUp 0.6s ease-out;
            position: relative;
        }

        /* Decorative corners */
        .reset-card::before,
        .reset-card::after {
            content: '✧';
            position: absolute;
            font-size: 20px;
            color: #D4AF37;
            opacity: 0.6;
        }
        .reset-card::before { top: 20px; left: 25px; }
        .reset-card::after { bottom: 20px; right: 25px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Icon */
        .icon-wrapper {
            text-align: center;
            margin-bottom: 25px;
        }
        .icon-wrapper i {
            font-size: 55px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .reset-card h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 34px;
            font-weight: 700;
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            font-size: 14px;
            color: #888;
            margin-bottom: 30px;
        }

        /* Kata-kata inspiratif di dalam card */
        .inspire-text {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(212,175,55,0.05), rgba(212,175,55,0.02));
            border-radius: 20px;
        }
        .inspire-text p {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-style: italic;
            color: #b48c5c;
        }
        .inspire-text i {
            color: #D4AF37;
            margin: 0 5px;
        }

        /* Form */
        .input-group {
            margin-bottom: 24px;
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
            margin: 15px 0 20px;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35);
        }

        .error-msg, .success-msg {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            background: #f8f6f3;
            padding: 20px;
            border-radius: 24px;
            margin: 20px 0;
            text-align: center;
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
        }
        .copy-btn {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            color: white;
            font-size: 12px;
            cursor: pointer;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        .back-link a {
            color: #b48c5c;
            text-decoration: none;
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
        }

        @media (max-width: 550px) {
            .reset-card {
                padding: 35px 25px;
            }
            .reset-card h1 {
                font-size: 28px;
            }
            .flower-left, .flower-right {
                width: 100px;
            }
            .flower-small {
                display: none;
            }
        }
    </style>
</head>
<body>
    <video class="bg-video" autoplay muted loop playsinline>
        <source src="https://assets.mixkit.co/videos/preview/mixkit-wedding-couple-hands-and-bouquet-39874-large.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <!-- BUNGA BESAR DI SAMPING KIRI KANAN -->
    <svg class="flower-large flower-left" viewBox="0 0 100 100">
        <path d="M50,15 Q70,30 85,50 Q70,70 50,85 Q30,70 15,50 Q30,30 50,15Z" fill="#FFD700" opacity="0.7"/>
        <circle cx="50" cy="50" r="15" fill="#FFA500"/>
        <circle cx="50" cy="35" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="65" cy="50" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="50" cy="65" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="35" cy="50" r="5" fill="#FFF" opacity="0.8"/>
    </svg>

    <svg class="flower-large flower-right" viewBox="0 0 100 100">
        <path d="M50,15 Q70,30 85,50 Q70,70 50,85 Q30,70 15,50 Q30,30 50,15Z" fill="#F48FB1" opacity="0.7"/>
        <circle cx="50" cy="50" r="15" fill="#E91E63"/>
        <circle cx="50" cy="35" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="65" cy="50" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="50" cy="65" r="5" fill="#FFF" opacity="0.8"/>
        <circle cx="35" cy="50" r="5" fill="#FFF" opacity="0.8"/>
    </svg>

    <!-- BUNGA KECIL DEKORATIF -->
    <svg class="flower-small fs-1" viewBox="0 0 100 100">
        <path d="M50,20 Q60,40 80,50 Q60,60 50,80 Q40,60 20,50 Q40,40 50,20Z" fill="#FFD700" opacity="0.6"/>
        <circle cx="50" cy="50" r="8" fill="#FFA500"/>
    </svg>
    <svg class="flower-small fs-2" viewBox="0 0 100 100">
        <path d="M50,20 Q60,40 80,50 Q60,60 50,80 Q40,60 20,50 Q40,40 50,20Z" fill="#F48FB1" opacity="0.6"/>
        <circle cx="50" cy="50" r="8" fill="#E91E63"/>
    </svg>
    <svg class="flower-small fs-3" viewBox="0 0 100 100">
        <path d="M50,20 Q60,40 80,50 Q60,60 50,80 Q40,60 20,50 Q40,40 50,20Z" fill="#FFD700" opacity="0.5"/>
        <circle cx="50" cy="50" r="6" fill="#FFA500"/>
    </svg>
    <svg class="flower-small fs-4" viewBox="0 0 100 100">
        <path d="M50,20 Q60,40 80,50 Q60,60 50,80 Q40,60 20,50 Q40,40 50,20Z" fill="#F48FB1" opacity="0.5"/>
        <circle cx="50" cy="50" r="6" fill="#E91E63"/>
    </svg>

    <div id="petals-container"></div>

    <div class="container">
        <div class="reset-card">
            <div class="icon-wrapper">
                <i class="fas fa-key"></i>
            </div>

            <?php if($step == 1): ?>
                <h1>Forgot Password?</h1>
                <p class="subtitle">Don't worry, we've got your back</p>

                <div class="inspire-text">
                    <p><i class="fas fa-heart"></i> "Every love story is beautiful, but yours deserves to be perfectly crafted." <i class="fas fa-heart"></i></p>
                </div>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <label>EMAIL ADDRESS</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                    <button type="submit" name="request_reset" class="btn">Send Reset Code</button>
                    <div class="back-link"><a href="index.php">← Back to Login</a></div>
                </form>
                
            <?php elseif($step == 2): ?>
                <h1>Reset Password</h1>
                <p class="subtitle">Enter the token and your new password</p>

                <div class="inspire-text">
                    <p><i class="fas fa-star"></i> "Your journey to forever starts with a single step." <i class="fas fa-star"></i></p>
                </div>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="token-card">
                    <strong>Your Reset Token:</strong>
                    <div class="token-code" id="tokenCode"><?php echo $_SESSION['reset_token']; ?></div>
                    <button onclick="copyToken()" class="copy-btn"><i class="fas fa-copy"></i> Copy Token</button>
                </div>
                
                <form method="POST">
                    <div class="input-group">
                        <label>RESET TOKEN</label>
                        <input type="text" name="token" placeholder="Enter reset token" required>
                    </div>
                    <div class="input-group">
                        <label>NEW PASSWORD</label>
                        <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="input-group">
                        <label>CONFIRM PASSWORD</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn">Reset Password</button>
                    <div class="back-link"><a href="index.php">← Back to Login</a></div>
                </form>
                
            <?php elseif($step == 3): ?>
                <h1>Success! 🎉</h1>
                <p class="subtitle">Your password has been reset</p>

                <div class="inspire-text">
                    <p><i class="fas fa-crown"></i> "Now back to planning your perfect day!" <i class="fas fa-crown"></i></p>
                </div>
                
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                
                <a href="index.php" class="success-btn">Login Now →</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function createPetal() {
            const petals = ['🌸', '🌹', '🌺', '🌼', '🌸', '🌷', '🥀', '🌸', '🌼'];
            const petal = document.createElement('div');
            petal.className = 'petal';
            petal.innerHTML = petals[Math.floor(Math.random() * petals.length)];
            petal.style.left = Math.random() * 100 + '%';
            petal.style.animationDuration = Math.random() * 6 + 4 + 's';
            petal.style.animationDelay = Math.random() * 5 + 's';
            petal.style.fontSize = Math.random() * 16 + 14 + 'px';
            document.body.appendChild(petal);
            setTimeout(() => petal.remove(), 12000);
        }
        setInterval(createPetal, 600);

        function copyToken() {
            var token = document.getElementById("tokenCode").innerText;
            navigator.clipboard.writeText(token).then(function() {
                alert("✅ Token copied successfully!");
            });
        }
    </script>
</body>
</html>