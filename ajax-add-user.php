<?php
session_start();
require_once 'php/config.php';

if(!isset($_SESSION['admin_logged_in'])) {
    echo "error: not logged in";
    exit();
}

$username = mysqli_real_escape_string($conn, $_POST['username']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = mysqli_real_escape_string($conn, $_POST['role']);

$check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
if(mysqli_num_rows($check) > 0) {
    echo "error: username or email already exists";
    exit();
}

$query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
if(mysqli_query($conn, $query)) {
    echo "success";
} else {
    echo "error: " . mysqli_error($conn);
}
?>