<?php
session_start();
require_once 'php/config.php';

if(!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
$amount = floatval($_POST['amount']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
$notes = mysqli_real_escape_string($conn, $_POST['notes']);

$query = "INSERT INTO payments (user_id, amount, payment_method, status, notes, payment_date) 
          VALUES ('$user_id', '$amount', '$payment_method', 'completed', '$notes', NOW())";

if(mysqli_query($conn, $query)) {
    echo json_encode(['status' => 'success', 'message' => 'Payment recorded!']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
?>