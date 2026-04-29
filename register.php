<?php
require_once 'php/config.php';

// Jika sudah login, langsung ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if(empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } elseif($password != $confirm_password) {
        $error = "Password dan Konfirmasi Password tidak sama!";
    } elseif(strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Cek username sudah ada atau belum
        $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error = "Username atau Email sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert data
            $insert_query = "INSERT INTO users (username, email, password, full_name, phone) 
                            VALUES ('$username', '$email', '$hashed_password', '$full_name', '$phone')";
            
            if(mysqli_query($conn, $insert_query)) {
                $success = "Pendaftaran berhasil! Silakan login.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Pendaftaran gagal: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Wedding Organizer</title>
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

        /* ========== MAIN CONTAINER ========== */
        .container {
            position: relative;
            z-index: 3;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 60px;
        }

        /* Content Grid */
        .content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1300px;
            gap: 50px;
        }

        /* ========== PANEL KIRI - KATA-KATA INSPIRATIF ========== */
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
            margin-bottom: 40px;
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

        /* Quote Section */
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

        /* Benefits List */
        .benefits {
            margin-top: 40px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .benefits h4 {
            font-size: 14px;
            letter-spacing: 2px;
            margin-bottom: 15px;
            color: #FFD700;
        }

        .benefits ul {
            list-style: none;
        }

        .benefits li {
            margin-bottom: 12px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .benefits li i {
            color: #FFD700;
            font-size: 14px;
        }

        /* ========== PANEL KANAN - FORM REGISTER ========== */
        .right-panel {
            flex: 0.9;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(15px);
            border-radius: 32px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInRight 0.8s ease-out;
            max-height: 85vh;
            overflow-y: auto;
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

        /* Scrollbar Styling */
        .right-panel::-webkit-scrollbar {
            width: 6px;
        }

        .right-panel::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .right-panel::-webkit-scrollbar-thumb {
            background: #b48c5c;
            border-radius: 10px;
        }

        /* Input Groups */
        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b48c5c;
            margin-bottom: 6px;
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

        /* Row untuk 2 kolom */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Button */
        .register-btn {
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
            margin: 20px 0 20px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(180,140,92,0.3);
        }

        /* Error & Success Messages */
        .error-msg, .success-msg {
            padding: 12px 15px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-msg {
            background: #fff5f0;
            border-left: 3px solid #e74c3c;
            color: #c0392b;
        }

        .success-msg {
            background: #e8f5e9;
            border-left: 3px solid #2e7d32;
            color: #2e7d32;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            font-size: 12px;
            color: #888;
            padding-top: 18px;
            border-top: 1px solid #f0f0f0;
        }

        .login-link a {
            color: #b48c5c;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1000px) {
            .container {
                padding: 30px;
            }
            
            .content {
                flex-direction: column;
                gap: 30px;
            }
            
            .left-panel {
                text-align: center;
            }
            
            .quote::before {
                left: -20px;
            }
            
            .right-panel {
                width: 100%;
                max-width: 550px;
                max-height: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .right-panel {
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 36px;
            }
            
            .quote {
                font-size: 28px;
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
            <!-- LEFT PANEL - Kata-kata Inspiratif -->
            <div class="left-panel">
                <div class="logo">
                    <h1>Wedding<br>Organizer</h1>
                    <p>ELEGANT WEDDING SUITE</p>
                </div>

                <div class="quote-section">
                    <div class="quote">
                        Begin your journey<br>to forever with us.
                    </div>
                    <div class="quote-author">— Create your love story</div>
                </div>

                <div class="benefits">
                    <h4>✦ WHY JOIN US ✦</h4>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Gratis pendaftaran seumur hidup</li>
                        <li><i class="fas fa-check-circle"></i> Akses semua fitur premium</li>
                        <li><i class="fas fa-check-circle"></i> Konsultasi dengan wedding planner</li>
                        <li><i class="fas fa-check-circle"></i> Template undangan eksklusif</li>
                        <li><i class="fas fa-check-circle"></i> Manajemen tamu digital</li>
                    </ul>
                </div>
            </div>

            <!-- RIGHT PANEL - Form Register -->
            <div class="right-panel">
                <h2>Create Account</h2>
                <div class="sub">Mulai perjalanan pernikahan impian Anda</div>

                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 2000);
                        </script>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label>FULL NAME</label>
                        <input type="text" name="full_name" placeholder="Nama lengkap" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label>USERNAME</label>
                            <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>PHONE (OPTIONAL)</label>
                            <input type="tel" name="phone" placeholder="Nomor telepon" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>EMAIL ADDRESS</label>
                        <input type="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label>PASSWORD</label>
                            <input type="password" name="password" placeholder="Min. 6 karakter" required>
                        </div>
                        <div class="input-group">
                            <label>CONFIRM PASSWORD</label>
                            <input type="password" name="confirm_password" placeholder="Konfirmasi password" required>
                        </div>
                    </div>

                    <button type="submit" class="register-btn">
                        <i class="fas fa-user-plus"></i> CREATE ACCOUNT
                    </button>

                    <div class="login-link">
                        Already have an account? <a href="index.php">Sign In →</a>
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