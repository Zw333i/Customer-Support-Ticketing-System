<?php
include "config.php";

$test_email = "zjuztine@gmail.com"; // Change to your test email
$subject = "Test Email from Support System";
$body = "<h1>This is a test email</h1><p>If you're seeing this, PHPMailer is working!</p>";

if (sendEmailNotification($test_email, $subject, $body)) {
    echo "Email sent successfully! Check your inbox (and spam folder).";
} else {
    echo "Failed to send email. Check error logs.";
}
?>