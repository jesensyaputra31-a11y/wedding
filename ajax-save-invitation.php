<?php
session_start();
require_once 'php/config.php';

if(!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$guest_name = mysqli_real_escape_string($conn, $_POST['guest_name']);
$guest_email = mysqli_real_escape_string($conn, $_POST['guest_email']);
$wedding_id = mysqli_real_escape_string($conn, $_POST['wedding_id']);

$query = "INSERT INTO invitations (guest_name, guest_email, wedding_id, status, sent_date) 
          VALUES ('$guest_name', '$guest_email', '$wedding_id', 'sent', NOW())";

if(mysqli_query($conn, $query)) {
    echo json_encode(['status' => 'success', 'message' => 'Invitation sent!']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
?>