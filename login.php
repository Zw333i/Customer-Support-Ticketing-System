<?php
session_start();
include "config.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === "admin@admin.com" && $password === "admin") {
        $_SESSION['user_id'] = "admin";
        $_SESSION['full_name'] = "Admin";
        $_SESSION['role'] = "admin";

        header("Location: admin_dashboard.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = "user";

            header("Location: index.php");
            exit();
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    } else {
        $error_message = "No account found with that email.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Support Desk</title>
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
        }
        
        body {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .login-container {
            background: var(--blue);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            background: var(--medium-blue);
            padding: 25px;
            text-align: center;
            border-bottom: 3px solid var(--baby-pink);
        }
        
        .login-header h2 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border: 1px solid var(--light-blue);
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border-color: var(--baby-pink);
            box-shadow: 0 0 0 0.25rem rgba(244, 165, 193, 0.25);
        }
        
        .btn-login {
            background-color: var(--baby-pink);
            border-color: var(--baby-pink);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 12px;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: var(--dark-pink);
            border-color: var(--dark-pink);
        }
        
        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: var(--light-blue);
        }
        
        .signup-link a {
            color: var(--baby-pink);
            text-decoration: none;
            font-weight: 600;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .password-field-wrapper {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light-blue);
            transition: color 0.3s ease;
        }
        
        .password-toggle-icon:hover {
            color: var(--baby-pink);
        }
        
        .password-field-wrapper .form-control {
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="bi bi-headset me-2"></i>Ticket Support</h2>
            <p class="text-light mb-0 mt-2">Sign in to your account</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-envelope me-2"></i>Email address</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-lock me-2"></i>Password</label>
                    <div class="password-field-wrapper">
                        <input type="password" class="form-control" id="password-field" name="password" required>
                        <i class="bi bi-eye-slash password-toggle-icon" id="password-toggle"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
            
            <div class="signup-link">
                <p>Don't have an account? <a href="register.php">Sign up here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password-field');
            const passwordToggle = document.getElementById('password-toggle');
            
            passwordToggle.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    passwordToggle.classList.remove('bi-eye-slash');
                    passwordToggle.classList.add('bi-eye');
                } else {
                    passwordField.type = 'password';
                    passwordToggle.classList.remove('bi-eye');
                    passwordToggle.classList.add('bi-eye-slash');
                }
            });
        });
    </script>
</body>
</html>