<?php
// File: hash.php
// Letak: C:\xampp\htdocs\wedding\hash.php

$password = "admin123"; // Ganti dengan password yang diinginkan
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "==================================<br>";
echo "     PASSWORD HASH GENERATOR      <br>";
echo "==================================<br><br>";
echo "Password yang dimasukkan: <strong>" . $password . "</strong><br><br>";
echo "Hash Bcrypt: <br>";
echo "<textarea rows='3' cols='70' style='font-family: monospace;'>" . $hash . "</textarea><br><br>";
echo "==================================<br>";
echo "Copy hash di atas dan gunakan untuk update password di database.<br>";
?>