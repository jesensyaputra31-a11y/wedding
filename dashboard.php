<?php
require_once 'php/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ambil data user dari database untuk cek role
$user_id = $_SESSION['user_id'];
$query_user = "SELECT username, full_name, role FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($result_user);

$displayName = isset($user_data['full_name']) ? $user_data['full_name'] : (isset($user_data['username']) ? $user_data['username'] : 'Bride');
$userRole = isset($user_data['role']) ? $user_data['role'] : 'user';

// Data wedding
$weddingDate = "2025-12-25 18:00:00";
$couple_name = "The Couple";
$venue_name = "The Grand Wedding Hall";
$total_guests = 250;
$confirmed_guests = 187;
$wedding_budget = 500000000;
$spent_budget = 325000000;

// ========== PROSES RATING PLANNER ==========
if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'rate_planner') {
    header('Content-Type: application/json');
    $planner_id = intval($_POST['planner_id']);
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    
    if($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rating']);
        exit();
    }
    
    // Cek apakah tabel planner_ratings ada, jika tidak buat
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'planner_ratings'");
    if(mysqli_num_rows($table_check) == 0) {
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
    
    // Cek apakah sudah pernah rating
    $check = mysqli_query($conn, "SELECT id, rating FROM planner_ratings WHERE planner_id = $planner_id AND user_id = $user_id");
    
    if(mysqli_num_rows($check) > 0) {
        $old_rating = mysqli_fetch_assoc($check)['rating'];
        $rating_diff = $rating - $old_rating;
        mysqli_query($conn, "UPDATE planner_ratings SET rating = $rating WHERE planner_id = $planner_id AND user_id = $user_id");
        mysqli_query($conn, "UPDATE wedding_planners SET total_ratings = total_ratings + $rating_diff WHERE id = $planner_id");
    } else {
        mysqli_query($conn, "INSERT INTO planner_ratings (planner_id, user_id, rating) VALUES ($planner_id, $user_id, $rating)");
        mysqli_query($conn, "UPDATE wedding_planners SET total_ratings = total_ratings + $rating, ratings_count = ratings_count + 1 WHERE id = $planner_id");
    }
    
    $planner_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_ratings, ratings_count FROM wedding_planners WHERE id = $planner_id"));
    $avg_rating = $planner_data['ratings_count'] > 0 ? round($planner_data['total_ratings'] / $planner_data['ratings_count'], 1) : 0;
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Rating saved!',
        'avg_rating' => $avg_rating,
        'ratings_count' => $planner_data['ratings_count'],
        'user_rating' => $rating
    ]);
    exit();
}

// ========== PROSES GET PLANNER DETAIL ==========
if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'get_planner_detail') {
    header('Content-Type: application/json');
    $planner_id = intval($_POST['planner_id']);
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT * FROM wedding_planners WHERE id = $planner_id";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $planner = mysqli_fetch_assoc($result);
        
        // Cek user rating
        $user_rating = 0;
        $rating_check = mysqli_query($conn, "SELECT rating FROM planner_ratings WHERE planner_id = $planner_id AND user_id = $user_id");
        if(mysqli_num_rows($rating_check) > 0) {
            $user_rating = mysqli_fetch_assoc($rating_check)['rating'];
        }
        
        echo json_encode([
            'status' => 'success',
            'planner' => $planner,
            'avg_rating' => $planner['ratings_count'] > 0 ? round($planner['total_ratings'] / $planner['ratings_count'], 1) : 4.8,
            'user_rating' => $user_rating
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Planner not found']);
    }
    exit();
}

// ========== CEK DAN BUAT TABEL WEDDING PLANNERS JIKA KOSONG ==========
$planner_table = mysqli_query($conn, "SHOW TABLES LIKE 'wedding_planners'");
if(mysqli_num_rows($planner_table) == 0) {
    mysqli_query($conn, "CREATE TABLE wedding_planners (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        weddings INT(11) DEFAULT 0,
        rating DECIMAL(2,1) DEFAULT 0,
        total_ratings INT DEFAULT 0,
        ratings_count INT DEFAULT 0,
        photo_url VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    // Insert 5 Wedding Planner
    mysqli_query($conn, "INSERT INTO wedding_planners (name, weddings, total_ratings, ratings_count, photo_url, phone, email, status) VALUES
        ('Sarah Wijaya', 45, 245, 50, 'https://randomuser.me/api/portraits/women/68.jpg', '+62 812 3456 7890', 'sarah@weddingplanner.com', 'active'),
        ('Budi Santoso', 38, 190, 40, 'https://randomuser.me/api/portraits/men/32.jpg', '+62 812 3456 7891', 'budi@weddingplanner.com', 'active'),
        ('Dewi Anjani', 52, 312, 64, 'https://randomuser.me/api/portraits/women/45.jpg', '+62 812 3456 7892', 'dewi@weddingplanner.com', 'active'),
        ('Andre Gunawan', 41, 205, 43, 'https://randomuser.me/api/portraits/men/55.jpg', '+62 812 3456 7893', 'andre@weddingplanner.com', 'active'),
        ('Maya Sari', 36, 180, 38, 'https://randomuser.me/api/portraits/women/89.jpg', '+62 812 3456 7894', 'maya@weddingplanner.com', 'active')");
}

// Ambil data wedding planners
$planners_for_user = mysqli_query($conn, "SELECT * FROM wedding_planners WHERE status='active' ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Wedding Organizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fefaf5;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ========== NAVBAR MODERN GOLD ========== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0,0,0,0.03), 0 1px 0 rgba(180,140,92,0.15);
            z-index: 1000;
            padding: 16px 40px;
            transition: all 0.3s ease;
        }

        .navbar-container {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .navbar-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 700;
            background: linear-gradient(135deg, #D4AF37, #FFD700, #B8860B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .navbar-logo span {
            background: linear-gradient(135deg, #1a1a2e, #2d2d44);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-link {
            text-decoration: none;
            color: #4a3b2c;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            padding: 10px 18px;
            border-radius: 40px;
            position: relative;
            font-family: 'Inter', sans-serif;
        }

        .nav-link:hover {
            color: #D4AF37;
            background: rgba(212, 175, 55, 0.08);
        }

        .nav-link i {
            margin-right: 8px;
            font-size: 13px;
        }

        .nav-link.active {
            color: #D4AF37;
            background: rgba(212, 175, 55, 0.12);
        }

        .logout-nav {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white !important;
            padding: 10px 24px;
            border-radius: 40px;
            margin-left: 12px;
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
        }

        .logout-nav:hover {
            background: linear-gradient(135deg, #E5C55A, #C9A03D);
            transform: translateY(-2px);
            color: white !important;
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
        }

        .logout-nav i {
            margin-right: 6px;
        }

        .admin-badge {
            background: rgba(212, 175, 55, 0.15);
            color: #D4AF37;
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 12px;
        }

        .menu-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: #D4AF37;
            background: rgba(212, 175, 55, 0.1);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background: rgba(212, 175, 55, 0.2);
        }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, rgba(0,0,0,0.65), rgba(30,15,20,0.6)), 
                        url('https://images.unsplash.com/photo-1519741497674-611481863552?w=1600');
            background-size: cover;
            background-position: center 40%;
            padding: 130px 40px 70px;
            position: relative;
            margin-top: 0;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            color: white;
            position: relative;
            z-index: 2;
        }

        .hero-content p:first-child {
            font-size: 14px;
            letter-spacing: 4px;
            color: #FFD700;
            margin-bottom: 15px;
        }

        .hero-content h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .couple-name {
            font-size: 22px;
            font-family: 'Cormorant Garamond', serif;
            color: #FFD700;
            margin-top: 15px;
            cursor: pointer;
        }

        .venue-text {
            margin-top: 15px;
            cursor: pointer;
            font-size: 14px;
            opacity: 0.85;
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1300px;
            margin: 40px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 5;
        }

        .section {
            scroll-margin-top: 90px;
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0e5dc;
        }

        .section-title i {
            color: #D4AF37;
            margin-right: 10px;
        }

        /* Admin Only Section */
        .admin-only {
            position: relative;
        }

        .admin-badge-section {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 15px;
            vertical-align: middle;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.12);
            border-color: rgba(212, 175, 55, 0.3);
        }

        .stat-card i {
            font-size: 36px;
            color: #D4AF37;
            margin-bottom: 12px;
        }

        .stat-card .value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .stat-card .label {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid rgba(212, 175, 55, 0.08);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(212, 175, 55, 0.1);
        }

        .card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0e5dc;
            cursor: pointer;
        }

        .card-title i {
            color: #D4AF37;
            margin-right: 8px;
        }

        #calendar {
            max-width: 100%;
            margin: 0 auto;
            cursor: pointer;
        }

        .fc-day {
            cursor: pointer;
        }

        .fc-daygrid-day-number {
            color: #4a3b2c;
        }

        .fc-col-header-cell-cushion {
            color: #D4AF37;
            font-weight: 600;
        }

        .todo-list {
            list-style: none;
        }

        .todo-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0e5dc;
            cursor: pointer;
            transition: background 0.2s;
            border-radius: 12px;
        }

        .todo-item:hover {
            background: #fef5e8;
            padding-left: 10px;
        }

        .todo-item input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #D4AF37;
            cursor: pointer;
        }

        .todo-item label {
            flex: 1;
            font-size: 14px;
            color: #4a3b2c;
            cursor: pointer;
        }

        .todo-item .date {
            font-size: 11px;
            color: #D4AF37;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .gallery-item {
            aspect-ratio: 1;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .gallery-item:hover {
            transform: scale(1.02);
            border-color: #D4AF37;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* TEAM GRID - BISA DIKLIK */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        .team-member {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            padding: 15px 10px;
            border-radius: 16px;
            background: #f8f6f3;
        }

        .team-member:hover {
            background: #fef5e8;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .team-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #D4AF37;
        }

        .team-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .team-role {
            font-size: 11px;
            color: #D4AF37;
        }

        /* Rating Stars */
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 3px;
            margin: 8px 0;
        }
        .rate-star {
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }
        .rate-star:hover {
            transform: scale(1.1);
        }
        .avg-rating {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3px;
            margin: 5px 0;
            font-size: 11px;
        }
        .your-rating {
            font-size: 10px;
            color: #D4AF37;
            margin-top: 3px;
        }

        /* FEATURES MENU */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .menu-card {
            background: white;
            padding: 25px 20px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.12);
            background: linear-gradient(135deg, white, #fefaf5);
            border-color: rgba(212, 175, 55, 0.3);
        }

        .menu-card i {
            font-size: 40px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }

        .menu-card h3 {
            font-size: 16px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .menu-card p {
            font-size: 12px;
            color: #888;
        }

        .progress-section {
            margin-top: 20px;
            cursor: pointer;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
            color: #666;
        }

        .progress-bar {
            height: 8px;
            background: #f0e5dc;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #D4AF37, #FFD700);
            border-radius: 10px;
            width: 0%;
        }

        .fullscreen-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .fullscreen-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .close-fullscreen {
            position: absolute;
            top: 30px;
            right: 40px;
            font-size: 40px;
            color: white;
            cursor: pointer;
            z-index: 2001;
            transition: 0.3s;
        }

        .close-fullscreen:hover {
            color: #D4AF37;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: modalFadeIn 0.3s ease;
            border-top: 4px solid #D4AF37;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            cursor: pointer;
            color: #888;
        }

        .modal-close:hover {
            color: #D4AF37;
        }

        .modal h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            margin-bottom: 20px;
            color: #1a1a2e;
        }

        .detail-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f6f3;
            border-radius: 12px;
        }
        .detail-item strong {
            color: #D4AF37;
            display: inline-block;
            width: 100px;
        }

        .event-form input, .event-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
        }

        .event-form button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
        }

        .event-form button:hover {
            background: linear-gradient(135deg, #E5C55A, #C9A03D);
        }

        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 99;
            color: white;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }

        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #E5C55A, #C9A03D);
        }

        /* FOOTER STYLES */
        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: rgba(255,255,255,0.8);
            padding: 60px 40px 20px;
            margin-top: 60px;
            position: relative;
        }

        .footer::before {
            content: '❦';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 40px;
            color: #D4AF37;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: serif;
            background: #1a1a2e;
            border: 2px solid #D4AF37;
        }

        .footer-container {
            max-width: 1300px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .footer-logo h3 span {
            color: #D4AF37;
        }

        .footer-logo p {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.7;
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-link {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: #D4AF37;
            transform: translateY(-3px);
            color: #1a1a2e;
        }

        .footer-col h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #D4AF37;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: #D4AF37;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a i {
            font-size: 10px;
            color: #D4AF37;
        }

        .footer-links a:hover {
            color: #D4AF37;
            transform: translateX(5px);
        }

        .footer-contact {
            list-style: none;
        }

        .footer-contact li {
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            opacity: 0.8;
        }

        .footer-contact li i {
            color: #D4AF37;
            margin-top: 3px;
            min-width: 16px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 25px;
            margin-top: 20px;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom-content p {
            font-size: 13px;
            opacity: 0.6;
        }

        .payment-methods {
            display: flex;
            gap: 15px;
            font-size: 24px;
            color: rgba(255,255,255,0.5);
        }

        .payment-methods i {
            transition: all 0.3s;
            cursor: pointer;
        }

        .payment-methods i:hover {
            color: #D4AF37;
            transform: translateY(-2px);
        }

        @media (max-width: 1100px) {
            .team-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: repeat(2, 1fr); }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .team-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .hero-content h1 { font-size: 40px; }
            .navbar { padding: 12px 20px; }
            .nav-menu { 
                display: none; 
                width: 100%; 
                flex-direction: column; 
                gap: 12px; 
                margin-top: 15px;
                background: white;
                padding: 20px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .nav-menu.active { display: flex; }
            .menu-toggle { display: flex; }
            .nav-link { width: 100%; text-align: center; }
            .logout-nav { margin-left: 0; text-align: center; }
        }

        @media (max-width: 600px) {
            .menu-grid { grid-template-columns: 1fr; }
            .gallery-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; gap: 30px; }
            .footer-bottom-content { flex-direction: column; text-align: center; }
            .footer { padding: 50px 20px 20px; }
            .footer::before { width: 50px; height: 50px; font-size: 30px; top: -25px; }
            .close-fullscreen { top: 20px; right: 20px; font-size: 30px; }
            .hero-content h1 br { display: block; }
        }
    </style>
</head>
<body>
    <!-- ========== NAVBAR MODERN GOLD ========== -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-logo" onclick="scrollToTop(); return false;">
                <span>Wedding</span> Organizer
            </a>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="#home" class="nav-link" onclick="closeMenu()"><i class="fas fa-home"></i> Home</a>
                <a href="#calendar-section" class="nav-link" onclick="closeMenu()"><i class="fas fa-calendar"></i> Calendar</a>
                <a href="#todolist-section" class="nav-link" onclick="closeMenu()"><i class="fas fa-check-double"></i> To-Do</a>
                <a href="#gallery-section" class="nav-link" onclick="closeMenu()"><i class="fas fa-images"></i> Gallery</a>
                <a href="#team-section" class="nav-link" onclick="closeMenu()"><i class="fas fa-users"></i> Team</a>
                <a href="#features-section" class="nav-link" onclick="closeMenu()"><i class="fas fa-star"></i> Features</a>
                <a href="#footer" class="nav-link" onclick="closeMenu()"><i class="fas fa-address-card"></i> Contact</a>
                <?php if($userRole == 'admin'): ?>
                <span class="admin-badge"><i class="fas fa-crown"></i> Admin</span>
                <?php endif; ?>
                <a href="logout.php" class="nav-link logout-nav" onclick="closeMenu()"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- ========== HERO SECTION ========== -->
    <section id="home" class="hero-header">
    <div class="hero-content">
        <p>✨ WELCOME TO YOUR WEDDING DASHBOARD ✨</p>
        <h1>Welcome Back,<br>Bride!</h1>
        <div class="couple-name" onclick="showNotification('❤️ The Couple ❤️')">
            <i class="fas fa-heart" style="color: #FFD700;"></i> <?php echo $couple_name; ?> <i class="fas fa-heart" style="color: #FFD700;"></i>
        </div>
        <div class="venue-text" onclick="showNotification('Venue: <?php echo $venue_name; ?>')">
            <i class="fas fa-map-marker-alt"></i> <?php echo $venue_name; ?>
        </div>
    </div>
</section>

    <div class="dashboard-container">
        <!-- ========== STATISTICS SECTION - ONLY ADMIN CAN SEE ========== -->
        <?php if($userRole == 'admin'): ?>
        <section id="statistics" class="section admin-only">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i> Wedding Statistics 
                <span class="admin-badge-section"><i class="fas fa-lock"></i> Admin Only</span>
            </h2>
            <div class="stats-grid">
                <div class="stat-card" onclick="showNotification('Total Guests: <?php echo number_format($total_guests); ?> people')">
                    <i class="fas fa-users"></i>
                    <div class="value"><?php echo number_format($total_guests); ?></div>
                    <div class="label">Total Guests</div>
                </div>
                <div class="stat-card" onclick="showNotification('Confirmed: <?php echo number_format($confirmed_guests); ?> guests will attend')">
                    <i class="fas fa-check-circle"></i>
                    <div class="value"><?php echo number_format($confirmed_guests); ?></div>
                    <div class="label">Confirmed</div>
                </div>
                <div class="stat-card" onclick="showNotification('Total Budget: Rp <?php echo number_format($wedding_budget); ?>')">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="value">Rp <?php echo number_format($wedding_budget/1000000); ?>M</div>
                    <div class="label">Total Budget</div>
                </div>
                <div class="stat-card" onclick="showNotification('Budget Used: <?php echo round(($spent_budget/$wedding_budget)*100); ?>% of total budget')">
                    <i class="fas fa-chart-line"></i>
                    <div class="value"><?php echo round(($spent_budget/$wedding_budget)*100); ?>%</div>
                    <div class="label">Budget Used</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ========== CALENDAR & TO-DO LIST ========== -->
        <div class="main-grid">
            <section id="calendar-section" class="section card">
                <div class="card-title" onclick="showNotification('📅 Click on any date to add an event!')">
                    <i class="fas fa-calendar-alt"></i> Wedding Calendar
                </div>
                <div id="calendar"></div>
            </section>

            <section id="todolist-section" class="section card">
                <div class="card-title" onclick="showNotification('✅ Check your wedding preparation tasks!')">
                    <i class="fas fa-check-double"></i> Wedding To-Do List
                </div>
                <ul class="todo-list">
                    <li class="todo-item" onclick="event.stopPropagation(); showTodoDetail('Confirm catering menu', 'Due in 2 days - Please contact the catering team to finalize the menu selection.')">
                        <input type="checkbox" id="todo1" onclick="event.stopPropagation()">
                        <label for="todo1">Confirm catering menu</label>
                        <span class="date">2 days left</span>
                    </li>
                    <li class="todo-item" onclick="event.stopPropagation(); showTodoDetail('Finalize decoration with WO', 'Due in 5 days - Schedule a meeting with Wedding Organizer to finalize decoration setup.')">
                        <input type="checkbox" id="todo2" onclick="event.stopPropagation()">
                        <label for="todo2">Finalize decoration with WO</label>
                        <span class="date">5 days left</span>
                    </li>
                    <li class="todo-item" onclick="event.stopPropagation(); showTodoDetail('Send invitation batch 2', 'Due in 7 days - Prepare and send the second batch of wedding invitations.')">
                        <input type="checkbox" id="todo3" onclick="event.stopPropagation()">
                        <label for="todo3">Send invitation batch 2</label>
                        <span class="date">7 days left</span>
                    </li>
                    <li class="todo-item" onclick="event.stopPropagation(); showTodoDetail('Wedding dress fitting', 'Due in 10 days - Final fitting session for wedding dress.')">
                        <input type="checkbox" id="todo4" onclick="event.stopPropagation()">
                        <label for="todo4">Wedding dress fitting</label>
                        <span class="date">10 days left</span>
                    </li>
                    <li class="todo-item" onclick="event.stopPropagation(); showTodoDetail('Meet with photographer', 'Due in 14 days - Discuss photo concept and pre-wedding schedule.')">
                        <input type="checkbox" id="todo5" onclick="event.stopPropagation()">
                        <label for="todo5">Meet with photographer</label>
                        <span class="date">14 days left</span>
                    </li>
                </ul>
                
                <div class="progress-section" onclick="showNotification('Budget Progress: <?php echo round(($spent_budget/$wedding_budget)*100); ?>% used')">
                    <div class="progress-label">
                        <span>Budget Progress</span>
                        <span><?php echo round(($spent_budget/$wedding_budget)*100); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo round(($spent_budget/$wedding_budget)*100); ?>%;"></div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ========== GALLERY SECTION ========== -->
        <section id="gallery-section" class="section card" style="margin-bottom: 30px;">
            <div class="card-title" onclick="showNotification('🖼️ Click on any photo to view FULLSCREEN!')">
                <i class="fas fa-images"></i> Wedding Gallery
            </div>
            <div class="gallery-grid">
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1519741497674-611481863552?w=800')">
                    <img src="https://suterahall.com/wp-content/uploads/2024/10/Harga-WO-Pernikahan-Wedding-Organizer-1024x585.jpg" alt="Wedding 1">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=800')">
                    <img src="https://lovary.co.id/userfiles/image_ck_by_user/15989/images/project%201/Our%20next%20chapter%20begins.%20Special%20thanks%20to%20our%20vendors-Wedding%20Organizer%20by%20%40yesido_official%20Venue%20by%20%40rumahduasejoli%20Wedding%20Ring%20by%20%40lovarycoid%20Seserahan%20%26%20Ring%20Box%20by%20%40seserahan.by.kala%20Mahar%20by%20%40treka.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?w=800')">
                    <img src="https://images.weddingku.com/images/upload/partners/73559/product/115587/images800/8585321262710704994.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=800')">
                    <img src="https://i.pinimg.com/736x/bf/df/d3/bfdfd344ee3899fefb53a7e3e41fdbba.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?w=800')">
                    <img src="https://www.passionjewelry.co.id/uploads/ini-dia-6-tugas-wedding-organizer-yang-kamu-perlu-tahu-2121-2020-12-30-085726.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1519741347686-c1e0aadf4611?w=800')">
                    <img src="https://i.pinimg.com/736x/79/04/9b/79049b8f19d7634e5d2e09c9ecd21e71.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?w=800')">
                    <img src="https://medinacatering.id/wp-content/uploads/2019/10/Catering-Set-Up-Gallery-6-768x960.jpg">
                </div>
                <div class="gallery-item" onclick="openFullscreen('https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=800')">
                    <img src="https://i.pinimg.com/736x/1e/4a/ed/1e4aed57703315b9f31c9bb2dc928605.jpg">
                </div>
            </div>
        </section>

        <!-- ========== TEAM SECTION - BISA DIKLICK ========== -->
        <section id="team-section" class="section card" style="margin-bottom: 30px;">
            <div class="card-title" onclick="showNotification('👥 Click on any planner to see contact details!')">
                <i class="fas fa-user-friends"></i> Wedding Planners
            </div>
            <div class="team-grid" id="user-planners-grid">
                <?php 
                $planners_for_user = mysqli_query($conn, "SELECT * FROM wedding_planners WHERE status='active' ORDER BY id ASC");
                while($p = mysqli_fetch_assoc($planners_for_user)): 
                    $avg_rating = $p['ratings_count'] > 0 ? round($p['total_ratings'] / $p['ratings_count'], 1) : ($p['rating'] > 0 ? $p['rating'] : 4.8);
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5;
                    
                    // Cek apakah user sudah pernah rating
                    $user_rating = 0;
                    $check_rating = mysqli_query($conn, "SELECT rating FROM planner_ratings WHERE planner_id = {$p['id']} AND user_id = {$user_id}");
                    if(mysqli_num_rows($check_rating) > 0) {
                        $user_rating = mysqli_fetch_assoc($check_rating)['rating'];
                    }
                ?>
                <div class="team-member" data-planner-id="<?php echo $p['id']; ?>" data-planner-name="<?php echo htmlspecialchars($p['name']); ?>" data-planner-phone="<?php echo $p['phone']; ?>" data-planner-email="<?php echo $p['email']; ?>" data-planner-weddings="<?php echo $p['weddings']; ?>" data-planner-photo="<?php echo $p['photo_url']; ?>" data-planner-ratings-count="<?php echo $p['ratings_count']; ?>" data-planner-total-ratings="<?php echo $p['total_ratings']; ?>" data-user-rating="<?php echo $user_rating; ?>" data-avg-rating="<?php echo $avg_rating; ?>">
                    <img src="<?php echo $p['photo_url']; ?>" class="team-img">
                    <div class="team-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="team-role">Professional Wedding Planner</div>
                    
                    <!-- Average Rating Display -->
                    <div class="avg-rating">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $full_stars): ?>
                                <i class="fas fa-star" style="color: #FFD700; font-size: 11px;"></i>
                            <?php elseif($i == $full_stars + 1 && $half_star): ?>
                                <i class="fas fa-star-half-alt" style="color: #FFD700; font-size: 11px;"></i>
                            <?php else: ?>
                                <i class="far fa-star" style="color: #ddd; font-size: 11px;"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span style="font-size: 10px; margin-left: 4px;">(<?php echo $p['ratings_count']; ?> reviews)</span>
                    </div>
                    
                    <div class="planner-stats" style="margin: 3px 0;">
                        <span style="font-size: 11px;">📋 <?php echo $p['weddings']; ?> weddings</span>
                    </div>
                    
                    <!-- User's Rating Display -->
                    <?php if($user_rating > 0): ?>
                        <div class="your-rating" id="your-rating-<?php echo $p['id']; ?>">
                            Your rating: 
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= $user_rating ? '#FFD700' : '#ddd'; ?>; font-size: 9px;"></i>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Star Rating Input -->
                    <div class="star-rating" data-planner-id="<?php echo $p['id']; ?>" onclick="event.stopPropagation()">
                        <i class="far fa-star rate-star" data-rating="1" style="cursor: pointer; color: #D4AF37; font-size: 15px;"></i>
                        <i class="far fa-star rate-star" data-rating="2" style="cursor: pointer; color: #D4AF37; font-size: 15px;"></i>
                        <i class="far fa-star rate-star" data-rating="3" style="cursor: pointer; color: #D4AF37; font-size: 15px;"></i>
                        <i class="far fa-star rate-star" data-rating="4" style="cursor: pointer; color: #D4AF37; font-size: 15px;"></i>
                        <i class="far fa-star rate-star" data-rating="5" style="cursor: pointer; color: #D4AF37; font-size: 15px;"></i>
                    </div>
                    <div style="font-size: 9px; color: #888; margin-top: 3px;">⭐ Click stars to rate</div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- ========== FEATURES SECTION ========== -->
        <section id="features-section" class="section">
            <h2 class="section-title"><i class="fas fa-star"></i> Wedding Features</h2>
            <div class="menu-grid">
                <div class="menu-card" onclick="showFeatureModal('Event Schedule')">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Event Schedule</h3>
                    <p>Manage wedding timeline</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Guest Management')">
                    <i class="fas fa-users"></i>
                    <h3>Guest Management</h3>
                    <p>Manage guest list</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Budget Planner')">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Budget</h3>
                    <p>Track wedding expenses</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Documentation')">
                    <i class="fas fa-camera-retro"></i>
                    <h3>Documentation</h3>
                    <p>Upload photos & videos</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Decoration')">
                    <i class="fas fa-chair"></i>
                    <h3>Decoration</h3>
                    <p>Choose theme & decor</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Catering')">
                    <i class="fas fa-utensils"></i>
                    <h3>Catering</h3>
                    <p>Menu & orders</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Entertainment')">
                    <i class="fas fa-music"></i>
                    <h3>Entertainment</h3>
                    <p>Music & performance</p>
                </div>
                <div class="menu-card" onclick="showFeatureModal('Invitation')">
                    <i class="fas fa-envelope"></i>
                    <h3>Invitation</h3>
                    <p>Send online invites</p>
                </div>
            </div>
        </section>
    </div>

    <!-- ========== FOOTER ========== -->
    <footer class="footer" id="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <h3><span>Wedding</span> Organizer</h3>
                        <p>Creating your perfect wedding day with elegance and love. We help you plan every detail of your special day.</p>
                    </div>
                    <div class="footer-social">
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-pinterest-p"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#calendar-section"><i class="fas fa-chevron-right"></i> Calendar</a></li>
                        <li><a href="#todolist-section"><i class="fas fa-chevron-right"></i> To-Do List</a></li>
                        <li><a href="#gallery-section"><i class="fas fa-chevron-right"></i> Gallery</a></li>
                        <li><a href="#team-section"><i class="fas fa-chevron-right"></i> Wedding Team</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Help Center</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                        <li><a href="#footer"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> Wedding Organizer HQ, Bali, Indonesia</li>
                        <li><i class="fas fa-phone"></i> +62 812 3456 7890</li>
                        <li><i class="fas fa-envelope"></i> info@weddingorganizer.com</li>
                        <li><i class="fas fa-clock"></i> Mon - Fri: 9:00 - 18:00</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; 2024 Wedding Organizer. All rights reserved. Made with <i class="fas fa-heart" style="color: #D4AF37;"></i> for your perfect day.</p>
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <div class="scroll-top" id="scrollTop" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- FULLSCREEN MODAL -->
    <div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
        <span class="close-fullscreen" onclick="closeFullscreen()">&times;</span>
        <img id="fullscreenImage" src="" alt="Fullscreen Wedding Photo">
    </div>

    <!-- Modal for Planner Detail (INFO LENGKAP) -->
    <div id="detailModal" class="modal" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Modal for Todo Detail -->
    <div id="todoModal" class="modal" onclick="closeModal('todoModal')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeModal('todoModal')">&times;</span>
            <h3 id="todoTitle"></h3>
            <p id="todoDetail"></p>
        </div>
    </div>

    <!-- Modal for Calendar Event -->
    <div id="eventModal" class="modal" onclick="closeModal('eventModal')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeModal('eventModal')">&times;</span>
            <h3>Add New Event</h3>
            <form id="eventForm" class="event-form">
                <input type="text" id="eventDate" placeholder="Date" readonly>
                <input type="text" id="eventTitle" placeholder="Event Title" required>
                <textarea id="eventDesc" placeholder="Description" rows="3"></textarea>
                <button type="submit">Add Event</button>
            </form>
        </div>
    </div>

    <!-- Modal for Features -->
    <div id="featureModal" class="modal" onclick="closeModal('featureModal')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeModal('featureModal')">&times;</span>
            <h3 id="featureTitle"></h3>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Detail Request:</label>
                <textarea id="featureDetails" rows="4" style="width:100%; padding:12px; border:1px solid #e0e0e0; border-radius:12px;" placeholder="Tulis detail request Anda di sini..."></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tanggal (opsional):</label>
                <input type="date" id="featureDate" style="width:100%; padding:12px; border:1px solid #e0e0e0; border-radius:12px;">
            </div>
            <button onclick="sendFeatureToAdmin()" style="width:100%; padding:14px; background:linear-gradient(135deg, #D4AF37, #B8860B); border:none; border-radius:40px; color:white; cursor:pointer; font-weight:600;">
                <i class="fas fa-paper-plane"></i> Kirim ke Admin
            </button>
        </div>
    </div>

    <script>
        // ========== NAVBAR FUNCTIONS ==========
        function toggleMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        function closeMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.remove('active');
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if(this.getAttribute('href') && this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        window.addEventListener('scroll', function() {
            const scrollTop = document.getElementById('scrollTop');
            if(window.scrollY > 300) {
                scrollTop.classList.add('show');
            } else {
                scrollTop.classList.remove('show');
            }
        });

        // ========== FULLSCREEN PHOTO ==========
        function openFullscreen(imageSrc) {
            document.getElementById('fullscreenImage').src = imageSrc;
            document.getElementById('fullscreenModal').style.display = 'flex';
        }

        function closeFullscreen() {
            document.getElementById('fullscreenModal').style.display = 'none';
        }

        // ========== NOTIFICATION ==========
        function showNotification(message) {
            const toast = document.createElement('div');
            toast.innerHTML = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                background: #1a1a2e;
                color: white;
                padding: 12px 24px;
                border-radius: 50px;
                font-size: 14px;
                z-index: 1001;
                animation: fadeInOut 3s ease;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                border-left: 4px solid #D4AF37;
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // ========== MODAL FUNCTIONS ==========
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) modal.style.display = 'none';
            else document.getElementById('detailModal').style.display = 'none';
        }

        // ========== SHOW PLANNER DETAIL (INFO LENGKAP) ==========
        function showPlannerDetail(plannerId, name, phone, email, weddings, photo, ratingsCount, totalRatings, userRating, avgRating) {
            // Create stars display
            let stars = '';
            let fullStars = Math.floor(avgRating);
            let halfStar = (avgRating - fullStars) >= 0.5;
            
            for(let i = 1; i <= 5; i++) {
                if(i <= fullStars) {
                    stars += '<i class="fas fa-star" style="color: #FFD700;"></i>';
                } else if(i == fullStars + 1 && halfStar) {
                    stars += '<i class="fas fa-star-half-alt" style="color: #FFD700;"></i>';
                } else {
                    stars += '<i class="far fa-star" style="color: #ddd;"></i>';
                }
            }
            
            let userStars = '';
            for(let i = 1; i <= 5; i++) {
                userStars += `<i class="fas fa-star" style="color: ${i <= userRating ? '#FFD700' : '#ddd'}; font-size: 11px;"></i>`;
            }
            
            let html = `
                <h3><i class="fas fa-crown" style="color: #D4AF37;"></i> ${name}</h3>
                <div style="text-align: center; margin: 15px 0;">
                    <img src="${photo}" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #D4AF37;">
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-star"></i> Rating:</strong> 
                    ${stars} <span style="margin-left: 8px;">(${avgRating} from ${ratingsCount} reviews)</span>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-calendar-check"></i> Weddings:</strong> <span style="color: #D4AF37;">${weddings}+</span> weddings successfully planned
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-envelope"></i> Email:</strong> 
                    <a href="mailto:${email}" style="color: #D4AF37; text-decoration: none;">${email}</a>
                </div>
                <div class="detail-item">
                    <strong><i class="fas fa-phone"></i> Phone:</strong> 
                    <a href="tel:${phone}" style="color: #D4AF37; text-decoration: none;">${phone}</a>
                </div>
                <div class="detail-item">
                    <strong><i class="fab fa-whatsapp"></i> WhatsApp:</strong> 
                    <a href="https://wa.me/${phone.replace(/[^0-9]/g, '')}" target="_blank" style="color: #25D366; text-decoration: none;">
                        Click to chat on WhatsApp
                    </a>
                </div>
                ${userRating > 0 ? `<div class="detail-item"><strong><i class="fas fa-user-check"></i> Your Rating:</strong> ${userStars}</div>` : '<div class="detail-item"><strong><i class="fas fa-star"></i> Your Rating:</strong> Not rated yet. Click stars above to rate!</div>'}
                <hr style="margin: 15px 0;">
                <div style="text-align: center;">
                    <a href="mailto:${email}" class="btn-submit" style="display: inline-block; width: auto; padding: 10px 20px; margin: 5px;">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                    <a href="https://wa.me/${phone.replace(/[^0-9]/g, '')}" target="_blank" class="btn-submit" style="display: inline-block; width: auto; padding: 10px 20px; margin: 5px; background: #25D366;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        // ========== HANDLE CLICK ON TEAM MEMBER ==========
        document.querySelectorAll('.team-member').forEach(member => {
            member.addEventListener('click', function(e) {
                // Jika yang diklik adalah star rating, jangan buka modal
                if(e.target.classList.contains('rate-star')) return;
                
                const plannerId = this.dataset.plannerId;
                const name = this.dataset.plannerName;
                const phone = this.dataset.plannerPhone;
                const email = this.dataset.plannerEmail;
                const weddings = this.dataset.plannerWeddings;
                const photo = this.dataset.plannerPhoto;
                const ratingsCount = this.dataset.plannerRatingsCount;
                const totalRatings = this.dataset.plannerTotalRatings;
                const userRating = parseInt(this.dataset.userRating) || 0;
                const avgRating = parseFloat(this.dataset.avgRating) || 4.8;
                
                showPlannerDetail(plannerId, name, phone, email, weddings, photo, ratingsCount, totalRatings, userRating, avgRating);
            });
        });

        // ========== STAR RATING FUNCTION ==========
        function initStarRating() {
            document.querySelectorAll('.star-rating').forEach(container => {
                const plannerId = container.dataset.plannerId;
                const stars = container.querySelectorAll('.rate-star');
                
                stars.forEach(star => {
                    star.style.cursor = 'pointer';
                    
                    star.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const rating = parseInt(this.dataset.rating);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `ajax_action=rate_planner&planner_id=${plannerId}&rating=${rating}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                showNotification(`⭐ Thank you for rating ${data.avg_rating}/5 stars!`);
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showNotification('❌ Failed to save rating');
                            }
                        })
                        .catch(() => showNotification('❌ Error saving rating'));
                    });
                    
                    star.addEventListener('mouseenter', function() {
                        const hoverRating = parseInt(this.dataset.rating);
                        stars.forEach((s, idx) => {
                            if(idx < hoverRating) {
                                s.classList.remove('far');
                                s.classList.add('fas');
                            } else {
                                s.classList.remove('fas');
                                s.classList.add('far');
                            }
                        });
                    });
                    
                    star.addEventListener('mouseleave', function() {
                        stars.forEach(s => {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        });
                    });
                });
            });
        }

        // ========== FEATURES ==========
        let currentFeature = '';

        function showFeatureModal(title) {
            currentFeature = title;
            document.getElementById('featureTitle').innerText = title;
            document.getElementById('featureDetails').value = '';
            document.getElementById('featureDate').value = '';
            document.getElementById('featureModal').style.display = 'flex';
        }

        function sendFeatureToAdmin() {
            const details = document.getElementById('featureDetails').value;
            const date = document.getElementById('featureDate').value;
            
            if(!details) {
                showNotification('❌ Silakan isi detail request Anda!');
                return;
            }
            
            const requestData = {
                id: Date.now(),
                feature: currentFeature,
                details: details,
                date: date,
                user: '<?php echo $displayName; ?>',
                username: '<?php echo $user_data['username']; ?>',
                user_id: '<?php echo $user_id; ?>',
                status: 'pending',
                timestamp: new Date().toISOString()
            };
            
            let requests = JSON.parse(localStorage.getItem('user_requests') || '[]');
            requests.unshift(requestData);
            localStorage.setItem('user_requests', JSON.stringify(requests));
            
            showNotification(`✅ Request "${currentFeature}" berhasil dikirim ke Admin!`);
            closeModal('featureModal');
        }

        function showTodoDetail(title, detail) {
            document.getElementById('todoTitle').innerText = title;
            document.getElementById('todoDetail').innerHTML = detail;
            document.getElementById('todoModal').style.display = 'flex';
        }

        // ========== CALENDAR ==========
        let selectedDate = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            initStarRating();
            
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth' },
                events: [
                    { title: '📋 Catering Tasting', start: '2025-12-10', color: '#D4AF37' },
                    { title: '🎨 Decoration Setup', start: '2025-12-24', color: '#D4AF37' },
                    { title: '💍 WEDDING DAY 💍', start: '2025-12-25', color: '#D4AF37', textColor: '#1a1a2e' }
                ],
                height: 350,
                dateClick: function(info) {
                    selectedDate = info.dateStr;
                    document.getElementById('eventDate').value = selectedDate;
                    document.getElementById('eventModal').style.display = 'flex';
                },
                eventClick: function(info) {
                    showNotification(`Event: ${info.event.title} on ${info.event.start.toDateString()}`);
                }
            });
            calendar.render();
        });

        document.getElementById('eventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('eventTitle').value;
            if(title) {
                showNotification(`✅ Event "${title}" added for ${selectedDate}`);
                closeModal('eventModal');
                document.getElementById('eventTitle').value = '';
                document.getElementById('eventDesc').value = '';
            }
        });

        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    if(modal.style.display === 'flex') modal.style.display = 'none';
                });
                closeFullscreen();
            }
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
    </script>
</body>
</html>