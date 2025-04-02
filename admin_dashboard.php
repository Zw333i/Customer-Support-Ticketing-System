<?php
// admin_dashboard.php
include "config.php";
include "session.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    
    $delete_replies = $conn->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?");
    $delete_replies->bind_param("i", $ticket_id);
    $delete_replies->execute();
    $delete_replies->close();
    
    $delete_ticket = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $delete_ticket->bind_param("i", $ticket_id);
    $delete_ticket->execute();
    $delete_ticket->close();
    
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $ticket_id = $_POST['ticket_id'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $ticket_id);
    
    if ($stmt->execute()) {
        $email_query = $conn->prepare("SELECT email FROM tickets WHERE id = ?");
        $email_query->bind_param("i", $ticket_id);
        $email_query->execute();
        $email_result = $email_query->get_result();
        $ticket_email = $email_result->fetch_assoc()['email'];
        $email_query->close();
        
        $subject = "Ticket #{$ticket_id} Status Updated";
        $body = "
            <h2>Ticket Status Updated</h2>
            <p>Your ticket #{$ticket_id} status has been updated to: <strong>{$new_status}</strong></p>
            <p>Thank you for your patience.</p>
        ";
        
        sendEmailNotification($ticket_email, $subject, $body);
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reply'])) {
    $ticket_id = $_POST['ticket_id'];
    $admin_message = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, NULL, ?)");
    $stmt->bind_param("is", $ticket_id, $admin_message);
    
    if ($stmt->execute()) {
        $email_query = $conn->prepare("SELECT email FROM tickets WHERE id = ?");
        $email_query->bind_param("i", $ticket_id);
        $email_query->execute();
        $email_result = $email_query->get_result();
        $ticket_email = $email_result->fetch_assoc()['email'];
        $email_query->close();
        
        $subject = "New Reply on Ticket #{$ticket_id}";
        $body = "
            <h2>New Reply Added</h2>
            <p>The support team has replied to your ticket #{$ticket_id}:</p>
            <blockquote>{$admin_message}</blockquote>
            <p>You can view the ticket for more details.</p>
        ";
        
        sendEmailNotification($ticket_email, $subject, $body);
        
        $user_query = $conn->prepare("
            SELECT DISTINCT u.email 
            FROM ticket_replies tr 
            JOIN users u ON tr.user_id = u.id
            WHERE tr.ticket_id = ? AND tr.user_id IS NOT NULL
        ");
        $user_query->bind_param("i", $ticket_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        
        while ($user = $user_result->fetch_assoc()) {
            if ($user['email'] != $ticket_email) { 
                sendEmailNotification($user['email'], $subject, $body);
            }
        }
        $user_query->close();
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT id, customer_name, email, issue, status, created_at FROM tickets";
$where_clauses = [];
$params = [];
$param_types = "";

if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($search_term) {
    $where_clauses[] = "(customer_name LIKE ? OR email LIKE ? OR issue LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $param_types .= "sss";
}

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result();
if (!$tickets) {
    die("Error fetching tickets: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dark-blue: #0d1b2a;
            --blue: #1b263b;
            --medium-blue: #415a77;
            --light-blue: #778da9;
            --baby-pink: #f4a5c1;
            --dark-pink: #d484a8;
            --black: #121212;
        }
        
        body {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
            min-height: 100vh;
            margin: 0;
        }
        
        .navbar {
            background-color: var(--blue);
            border-bottom: 3px solid var(--baby-pink);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .dashboard-container {
            background: var(--blue);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--medium-blue);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h2 {
            color: var(--baby-pink);
            font-weight: 600;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--baby-pink);
            padding-bottom: 10px;
            text-align: center;
        }
        
        .btn-custom-primary {
            background-color: var(--baby-pink);
            border-color: var(--baby-pink);
            color: var(--dark-blue);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom-primary:hover {
            background-color: var(--dark-pink);
            border-color: var(--dark-pink);
            color: var(--dark-blue);
            transform: translateY(-2px);
        }
        
        .btn-custom-secondary {
            background-color: var(--medium-blue);
            border-color: var(--medium-blue);
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom-secondary:hover {
            background-color: var(--light-blue);
            border-color: var(--light-blue);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-custom-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom-danger:hover {
            background-color: #bb2d3b;
            border-color: #bb2d3b;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-custom-info {
            background-color: var(--light-blue);
            border-color: var(--light-blue);
            color: var(--dark-blue);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom-info:hover {
            background-color: var(--baby-pink);
            border-color: var(--baby-pink);
            color: var(--dark-blue);
            transform: translateY(-2px);
        }
        
        .form-control, .form-select {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border: 1px solid var(--light-blue);
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border-color: var(--baby-pink);
            box-shadow: 0 0 0 0.25rem rgba(244, 165, 193, 0.25);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--medium-blue);
            margin-bottom: 30px;
        }
        
        .table thead {
            background-color: var(--medium-blue);
            color: white;
        }
        
        .table tbody tr {
            background-color: var(--blue);
            color: white;
        }
        
        .table tbody tr:hover {
            background-color: var(--medium-blue);
        }
        
        .card {
            background-color: var(--blue);
            border: 1px solid var(--medium-blue);
        }
        
        .card-body {
            background-color: var(--blue);
            color: white;
        }
        
        .text-muted {
            color: var(--light-blue) !important;
        }
        
        hr {
            background-color: var(--medium-blue);
            opacity: 0.5;
        }
        
        .status-dropdown {
            width: 100%;
        }
        
        .status-badge {
            width: 100%;
            display: block;
            text-align: center;
            padding: 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        
        .badge-progress {
            background-color: #0dcaf0;
            color: #000;
        }
        
        .badge-resolved {
            background-color: #198754;
            color: #fff;
        }
        
        .filter-section {
            background-color: var(--medium-blue);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            min-width: 120px;
        }
        
        .status-button {
            width: 100%;
            text-align: left;
            margin-bottom: 2px;
            border: none;
        }
        
        .dropdown-menu {
            width: 100%;
            background-color: var(--dark-blue);
            border: 1px solid var(--light-blue);
        }
        
        .dropdown-item {
            color: var(--baby-pink);
        }
        
        .dropdown-item:hover {
            background-color: var(--medium-blue);
            color: white;
        }
        
        .dropdown-item.active {
            background-color: var(--baby-pink);
            color: var(--dark-blue);
        }
        
        .action-btn {
            margin: 2px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><strong style="color: var(--baby-pink);">Support</strong> Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-container">
            <h2><i class="bi bi-shield-lock me-2"></i>Admin Dashboard</h2>
            
            <div class="filter-section">
                <form method="GET" action="admin_dashboard.php">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="search" class="form-label text-white"><i class="bi bi-search me-1"></i>Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email or issue..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label for="status" class="form-label text-white"><i class="bi bi-funnel me-1"></i>Filter by Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Tickets</option>
                                <option value="Open" <?php echo ($status_filter === 'Open') ? 'selected' : ''; ?>>Open</option>
                                <option value="In Progress" <?php echo ($status_filter === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Resolved" <?php echo ($status_filter === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-custom-primary w-100">
                                <i class="bi bi-filter me-1"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th><i class="bi bi-ticket-perforated me-1"></i>Ticket ID</th>
                            <th><i class="bi bi-person me-1"></i>User</th>
                            <th><i class="bi bi-envelope me-1"></i>Email</th>
                            <th><i class="bi bi-exclamation-circle me-1"></i>Issue</th>
                            <th><i class="bi bi-tag me-1"></i>Status</th>
                            <th><i class="bi bi-arrow-repeat me-1"></i>Update Status</th>
                            <th><i class="bi bi-chat-dots me-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets->num_rows > 0): ?>
                            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['email']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['issue']); ?></td>
                                    <td>
                                        <span class="status-badge <?php 
                                            if ($ticket['status'] == 'Open') echo 'badge-pending';
                                            else if ($ticket['status'] == 'In Progress') echo 'badge-progress';
                                            else if ($ticket['status'] == 'Resolved') echo 'badge-resolved';
                                        ?>">
                                            <?php echo htmlspecialchars($ticket['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle status-button 
                                                <?php 
                                                if ($ticket['status'] == 'Open') echo 'btn-warning';
                                                else if ($ticket['status'] == 'In Progress') echo 'btn-info';
                                                else if ($ticket['status'] == 'Resolved') echo 'btn-success';
                                                ?>" 
                                                type="button" id="statusDropdown<?php echo $ticket['id']; ?>" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <?php echo htmlspecialchars($ticket['status']); ?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $ticket['id']; ?>">
                                                <li>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="status" value="Open">
                                                        <button type="submit" class="dropdown-item <?php echo ($ticket['status'] == 'Open') ? 'active' : ''; ?>">
                                                            Open
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="status" value="In Progress">
                                                        <button type="submit" class="dropdown-item <?php echo ($ticket['status'] == 'In Progress') ? 'active' : ''; ?>">
                                                            In Progress
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="status" value="Resolved">
                                                        <button type="submit" class="dropdown-item <?php echo ($ticket['status'] == 'Resolved') ? 'active' : ''; ?>">
                                                            Resolved
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column flex-md-row justify-content-center">
                                            <button class="btn btn-custom-info btn-sm action-btn" data-bs-toggle="collapse" data-bs-target="#replies_<?php echo $ticket['id']; ?>">
                                                <i class="bi bi-eye me-1"></i>View Replies
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <input type="hidden" name="delete_ticket" value="1">
                                                <button type="submit" class="btn btn-custom-danger btn-sm action-btn">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="p-0">
                                        <div class="collapse" id="replies_<?php echo $ticket['id']; ?>">
                                            <div class="card card-body">
                                                <h5 class="text-white"><i class="bi bi-chat-left-text me-2"></i>Replies:</h5>
                                                <?php
                                                $stmt = $conn->prepare("SELECT user_id, message, created_at FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
                                                $stmt->bind_param("i", $ticket['id']);
                                                $stmt->execute();
                                                $replies = $stmt->get_result();
                                                
                                                if ($replies->num_rows > 0) {
                                                    while ($reply = $replies->fetch_assoc()): ?>
                                                        <div class="p-3 mb-2 <?php echo (is_null($reply['user_id'])) ? 'bg-medium-blue rounded-3' : 'bg-dark rounded-3'; ?>">
                                                            <p>
                                                                <strong class="<?php echo (is_null($reply['user_id'])) ? 'text-warning' : 'text-info'; ?>">
                                                                    <i class="bi <?php echo (is_null($reply['user_id'])) ? 'bi-person-gear' : 'bi-person'; ?> me-1"></i>
                                                                    <?php echo (is_null($reply['user_id'])) ? 'Admin' : 'User'; ?>:
                                                                </strong> 
                                                                <span class="text-light"><?php echo htmlspecialchars($reply['message']); ?></span>
                                                            </p>
                                                            <p class="text-muted mb-0">
                                                                <small><i class="bi bi-clock me-1"></i><?php echo $reply['created_at']; ?></small>
                                                            </p>
                                                        </div>
                                                    <?php endwhile;
                                                } else {
                                                    echo '<p class="text-white">No replies yet.</p>';
                                                }
                                                $stmt->close();
                                                ?>

                                                <form method="POST" class="mt-3">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label text-white"><i class="bi bi-reply me-2"></i>Reply to ticket:</label>
                                                        <textarea name="message" class="form-control" rows="3" placeholder="Type your reply here..." required></textarea>
                                                    </div>
                                                    <button type="submit" name="send_reply" class="btn btn-custom-primary">
                                                        <i class="bi bi-send me-2"></i>Send Reply
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No tickets found. Try changing your search filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>