<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_id = $_POST['ticket_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $ticket_id, $user_id, $message);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Reply added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding reply: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: index.php");
    exit();
}
?>