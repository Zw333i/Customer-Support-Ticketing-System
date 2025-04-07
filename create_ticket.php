<?php 
include "config.php"; 
include "session.php";
// create_ticket.php
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : "uploads/default.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Ticket</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        
        .navbar {
            background-color: var(--blue);
            border-bottom: 3px solid var(--baby-pink);
        }
        
        .content-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        .ticket-container {
            background: var(--blue);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--medium-blue);
            width: 100%;
            max-width: 650px;
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
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.2);
            color: #75b798;
            border-color: #0f5132;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ea868f;
            border-color: #842029;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><strong style="color: var(--baby-pink);">Support</strong> Dashboard</a>
            
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

    <div class="content-container">
        <div class="ticket-container">
            <h2><i class="bi bi-ticket-perforated me-2"></i>Create New Support Ticket</h2>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="store_ticket.php" method="POST">
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-person me-2"></i>Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" required placeholder="Enter customer's full name">
                </div>
                
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="Enter customer's email address">
                </div>
                
            <div class="mb-3">
                <label for="issue_type" class="form-label"><i class="bi bi-tag me-2"></i>Issue Type</label>
                <select class="form-select" id="issue_type" name="issue_type" required>
                    <option value="Other Reasons..." selected>Other Reasons...</option>
                    <option value="Service Issue">Service Issue (Get your internet back on track quickly. Report your internet service concern)</option>
                    <option value="Personnel Concerns">Personnel Concerns (Tell us about your experience with our authorized technician, contractor, employee or field agent)</option>
                    <option value="Clarify Bill Charges">Clarify Bill Charges (To know more about billing charges and get assistance in understanding your bill)</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="issue" class="form-label"><i class="bi bi-exclamation-circle me-2"></i>Issue Details</label>
                <textarea class="form-control" id="issue" name="issue" rows="5" required></textarea>
            </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                    <button type="submit" class="btn btn-custom-primary px-4">
                        <i class="bi bi-send me-2"></i>Submit Ticket
                    </button>
                    <a href="index.php" class="btn btn-custom-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>