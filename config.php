<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'wedding_organizer';

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>