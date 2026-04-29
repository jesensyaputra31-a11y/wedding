<?php
require_once 'php/config.php';

$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
$query = "UPDATE admins SET password = '$new_hash' WHERE username = 'admin'";

if(mysqli_query($conn, $query)) {
    echo "✅ Password berhasil diupdate!<br>";
    echo "Hash baru: " . $new_hash . "<br>";
    echo "<br><a href='admin-login.php'>Klik disini untuk login</a>";
} else {
    echo "❌ Gagal update: " . mysqli_error($conn);
}
?>