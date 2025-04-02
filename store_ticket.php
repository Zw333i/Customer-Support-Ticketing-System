<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    die("Error: User is not logged in. <a href='login.php'>Login</a>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"]; 
    $name = $_POST["customer_name"];
    $email = $_POST["email"];
    $issue = $_POST["issue"];
    $status = "Open";
    $created_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO tickets (user_id, customer_name, email, issue, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $name, $email, $issue, $status, $created_at);

    if ($stmt->execute()) {
        $ticket_id = $stmt->insert_id; 
        
        $customer_subject = "Your Support Ticket #$ticket_id Has Been Created";
        $customer_body = "
            <h2>Thank You for Contacting Support</h2>
            <p><strong>Ticket ID:</strong> #$ticket_id</p>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Issue:</strong> $issue</p>
            <p><strong>Status:</strong> $status</p>
            <p>We'll review your ticket and get back to you soon.</p>
            <p>You can view your ticket status at any time by logging into your account.</p>
        ";
        
        sendEmailNotification($email, $customer_subject, $customer_body);
        
        $admin_subject = "New Support Ticket #$ticket_id Created";
        $admin_body = "
            <h2>New Support Ticket Submitted</h2>
            <p><strong>Ticket ID:</strong> #$ticket_id</p>
            <p><strong>User ID:</strong> $user_id</p>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Issue:</strong> $issue</p>
            <p>Please review and update the ticket status in the admin dashboard.</p>
        ";
        
        sendEmailNotification(SMTP_FROM, $admin_subject, $admin_body);
        
        $_SESSION['success_message'] = "Ticket submitted successfully! We've sent a confirmation to your email.";
    } else {
        $_SESSION['error_message'] = "Error submitting ticket: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    
    header("Location: index.php");
    exit();
}
?>