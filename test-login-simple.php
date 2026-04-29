<?php
require_once 'php/config.php';

echo "<h2>=== DIAGNOSA MASALAH ===</h2>";

// 1. Cek koneksi database
if($conn) {
    echo "✅ 1. Koneksi database: OK<br>";
} else {
    echo "❌ 1. Koneksi database: GAGAL<br>";
}

// 2. Cek tabel admins
$cek = mysqli_query($conn, "SELECT * FROM admins");
if(mysqli_num_rows($cek) > 0) {
    $admin = mysqli_fetch_assoc($cek);
    echo "✅ 2. Tabel admins: ADA data<br>";
    echo "   - Username: " . $admin['username'] . "<br>";
    echo "   - Role: " . $admin['role'] . "<br>";
    
    // 3. Cek password
    if(password_verify('admin123', $admin['password'])) {
        echo "✅ 3. Password 'admin123': COCOK<br>";
    } else {
        echo "❌ 3. Password 'admin123': TIDAK COCOK<br>";
    }
} else {
    echo "❌ 2. Tabel admins: KOSONG! <br>";
    echo "   -> Jalankan query INSERT admin<br>";
}

// 4. Cek session
session_start();
echo "✅ 4. Session: BERJALAN<br>";

echo "<hr>";
echo "<h3>SOLUSI:</h3>";
if(mysqli_num_rows($cek) == 0) {
    echo "- Jalankan query INSERT ke tabel admins<br>";
} elseif(!password_verify('admin123', $admin['password'])) {
    echo "- Update password admin dengan hash yang benar<br>";
} else {
    echo "- Coba login lagi di admin-login.php<br>";
    echo "- Username: admin<br>";
    echo "- Password: admin123<br>";
}
?>