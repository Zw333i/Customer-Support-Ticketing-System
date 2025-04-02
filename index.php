<?php include "config.php"; ?>
<?php include "session.php"; ?>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">'.$_SESSION['success_message'].'</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">'.$_SESSION['error_message'].'</div>';
    unset($_SESSION['error_message']);
}
?>

<?php
$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : "uploads/default.png";

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT t.*, CASE WHEN t.user_id = ? THEN 1 ELSE 0 END AS is_owner FROM tickets t WHERE 1=1";
$params = array($_SESSION['user_id']);
$types = 'i';

if (!empty($search)) {
    $sql .= " AND (t.id = ? OR t.email LIKE ? OR t.issue LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'iss';
}

if (!empty($status_filter) && $status_filter !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Support Tickets</title>
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
        }
        
        .navbar {
            background-color: var(--blue);
            border-bottom: 3px solid var(--baby-pink);
        }
        
        .main-container {
            background: var(--blue);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            margin-top: 20px;
            border: 1px solid var(--medium-blue);
        }
        
        h2 {
            color: var(--baby-pink);
            font-weight: 600;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--baby-pink);
            padding-bottom: 10px;
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
        
        .btn-custom-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
            transform: translateY(-2px);
        }
        
        .dropdown-menu {
            background: var(--black);
            border: 1px solid var(--baby-pink);
        }
        
        .dropdown-item {
            color: var(--baby-pink);
        }
        
        .dropdown-item:hover {
            background: var(--medium-blue);
            color: white;
        }
        
        .table {
            background: var(--dark-blue);
            color: var(--baby-pink);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--medium-blue);
            color: white;
            border-color: var(--light-blue);
            font-weight: 600;
        }
        
        .table td {
            border-color: var(--light-blue);
            vertical-align: middle;
        }
        
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .profile-pic {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--baby-pink);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-pic:hover {
            border-color: white;
            transform: scale(1.05);
        }
        
        .profile-name {
            font-size: 16px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .search-filter-container {
            margin-bottom: 25px;
            background: var(--medium-blue);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        
        .form-label {
            color: white;
            font-weight: 500;
        }
        
        .ticket-row:hover {
            background-color: rgba(65, 90, 119, 0.3);
        }
        
        .reply-message {
            color: white;
            background-color: var(--medium-blue);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--baby-pink);
        }
        
        .badge-custom {
            background-color: var(--baby-pink);
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        .collapsed-content {
            background-color: var(--black);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid var(--medium-blue);
        }
        
        .btn-sm {
            border-radius: 20px;
            padding: 5px 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .owner-badge {
            background-color: var(--medium-blue);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
        }
        
        .delete-confirmation-modal .modal-content {
            background-color: var(--blue);
            color: var(--baby-pink);
            border: 1px solid var(--medium-blue);
        }
        
        .delete-confirmation-modal .modal-header {
            border-bottom: 1px solid var(--medium-blue);
        }
        
        .delete-confirmation-modal .modal-footer {
            border-top: 1px solid var(--medium-blue);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><strong style="color: var(--baby-pink);">Support</strong> Dashboard</a>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <div class="d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile Picture">
                        <span class="profile-name d-none d-md-inline"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <i class="bi bi-chevron-down ms-2 text-light"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-person-gear me-2"></i>Edit Profile</a></li>
                        <li><hr class="dropdown-divider" style="border-color: var(--medium-blue);"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-ticket-perforated me-2"></i>Support Tickets</h2>
            <a href="create_ticket.php" class="btn btn-custom-primary"><i class="bi bi-plus-circle me-2"></i>Create New Ticket</a>
        </div>
        
        <!-- Search and Filter Form -->
        <div class="search-filter-container">
            <form method="get" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="search" class="form-label"><i class="bi bi-search me-2"></i>Search Tickets</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by ID, email, or issue" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label"><i class="bi bi-funnel me-2"></i>Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' || empty($status_filter) ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mb-3">
                        <button type="submit" class="btn btn-custom-primary w-100"><i class="bi bi-filter me-2"></i>Apply</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Customer</th>
                        <th width="15%">Email</th>
                        <th width="20%">Issue</th>
                        <th width="10%">Status</th>
                        <th width="15%">Created</th>
                        <th width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="ticket-row">
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['customer_name']); ?>
                                    <?php if ($row['is_owner']): ?>
                                        <span class="owner-badge">Your Ticket</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($row['issue']); ?></td>
                                <td>
                                    <?php 
                                    $status_color = 'bg-secondary';
                                    switch($row['status']) {
                                        case 'Pending':
                                            $status_color = 'bg-warning text-dark';
                                            break;
                                        case 'In Progress':
                                            $status_color = 'bg-info text-dark';
                                            break;
                                        case 'Resolved':
                                            $status_color = 'bg-success';
                                            break;
                                        case 'Closed':
                                            $status_color = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_color; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-custom-primary" data-bs-toggle="collapse" data-bs-target="#replies_<?php echo $row['id']; ?>">
                                            <i class="bi bi-chat-dots me-1"></i> View Replies
                                        </button>
                                        
                                        <?php if ($row['is_owner']): ?>
                                            <button class="btn btn-sm btn-custom-danger" data-bs-toggle="modal" data-bs-target="#deleteModal_<?php echo $row['id']; ?>">
                                                <i class="bi bi-trash me-1"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7" style="padding: 0; border-top: none;">
                                    <div class="collapse" id="replies_<?php echo $row['id']; ?>">
                                        <div class="collapsed-content my-3">
                                            <h5 class="text-white mb-3"><i class="bi bi-chat-left-text me-2"></i>Conversation History</h5>
                                            <?php
                                            $stmt = $conn->prepare("SELECT 
                                                CASE 
                                                    WHEN user_id IS NULL THEN 'Admin' 
                                                    ELSE (SELECT full_name FROM users WHERE id = user_id) 
                                                END AS sender,
                                                message, 
                                                created_at 
                                                FROM ticket_replies 
                                                WHERE ticket_id = ? 
                                                ORDER BY created_at ASC");
                                            $stmt->bind_param("i", $row['id']);
                                            $stmt->execute();
                                            $replies = $stmt->get_result();
                                            
                                            if ($replies->num_rows > 0) {
                                                while ($reply = $replies->fetch_assoc()): ?>
                                                    <div class="reply-message">
                                                        <div class="d-flex justify-content-between">
                                                            <strong><?php echo htmlspecialchars($reply['sender']); ?></strong>
                                                            <small class="text-light"><?php echo date('M d, Y g:i A', strtotime($reply['created_at'])); ?></small>
                                                        </div>
                                                        <div class="mt-2">
                                                            <?php echo htmlspecialchars($reply['message']); ?>
                                                        </div>
                                                    </div>
                                                <?php endwhile;
                                            } else {
                                                echo '<div class="text-center py-4">
                                                    <i class="bi bi-chat-square-dots" style="font-size: 2rem; color: var(--light-blue);"></i>
                                                    <p class="text-light mt-2">No replies yet.</p>
                                                </div>';
                                            }
                                            $stmt->close();
                                            ?>
                                            
                                            <!-- Add Reply Form -->
                                            <form action="add_reply.php" method="post" class="mt-3">
                                                <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                <div class="form-group">
                                                    <textarea name="message" class="form-control" rows="3" placeholder="Type your reply here..." required></textarea>
                                                </div>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button type="submit" class="btn btn-custom-primary btn-sm">
                                                        <i class="bi bi-send me-1"></i> Send Reply
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Delete Confirmation Modal -->
                            <?php if ($row['is_owner']): ?>
                            <div class="modal fade delete-confirmation-modal" id="deleteModal_<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel_<?php echo $row['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel_<?php echo $row['id']; ?>">Confirm Deletion</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete ticket #<?php echo $row['id']; ?>?</p>
                                            <p class="text-light"><small>This action cannot be undone.</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form action="delete_ticket.php" method="post">
                                                <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-custom-danger">Delete Ticket</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-search" style="font-size: 2rem; color: var(--light-blue);"></i>
                                <p class="mt-2">No tickets found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>