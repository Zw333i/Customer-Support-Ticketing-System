<?php
include "config.php";
include "session.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];
    
    // First, verify that the ticket belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Ticket belongs to the user, so proceed with deletion
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete ticket replies first
            $stmt = $conn->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            
            // Then delete the ticket
            $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Ticket #" . $ticket_id . " has been successfully deleted.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error_message'] = "Error deleting ticket: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Error: You do not have permission to delete this ticket.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the tickets page
header("Location: index.php");
exit();
?>