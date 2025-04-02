<?php
include "config.php";

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $profile_image = "";

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Email already registered. Please use a different email.";
    } else {
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $profile_image = $target_dir . $new_filename;
            
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image)) {
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                    $profile_image = "";
                }
            } else {
                $error_message = "Only JPG, JPEG, PNG and GIF files are allowed.";
                $profile_image = "";
            }
        }

        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, profile_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $password, $profile_image);
            
            if ($stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
            } else {
                $error_message = "Registration failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Support Desk</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
        }
        
        .register-container {
            background: var(--blue);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            padding: 0;
            margin: 0 auto;
        }
        
        .register-header {
            background: var(--medium-blue);
            padding: 25px;
            text-align: center;
            border-bottom: 3px solid var(--baby-pink);
        }
        
        .register-header h2 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-control {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border: 1px solid var(--light-blue);
            padding: 12px 15px;
            height: auto;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background-color: var(--dark-blue);
            color: var(--baby-pink);
            border-color: var(--baby-pink);
            box-shadow: 0 0 0 0.25rem rgba(244, 165, 193, 0.25);
        }
        
        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .btn-custom-primary {
            background-color: var(--baby-pink);
            border-color: var(--baby-pink);
            color: var(--dark-blue);
            font-weight: 600;
            padding: 12px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .btn-custom-primary:hover {
            background-color: var(--dark-pink);
            border-color: var(--dark-pink);
            color: var(--dark-blue);
            transform: translateY(-2px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--light-blue);
        }
        
        .login-link a {
            color: var(--baby-pink);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.2);
            color: #75e596;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .form-file {
            position: relative;
        }
        
        .form-file-input {
            position: relative;
            z-index: 2;
            width: 100%;
            height: calc(3.5rem + 2px);
            margin: 0;
            opacity: 0;
        }
        
        .form-file-label {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1;
            height: calc(3.5rem + 2px);
            padding: 0.375rem 0.75rem;
            font-weight: 400;
            line-height: 1.5;
            color: #fff;
            background-color: var(--dark-blue);
            border: 1px solid var(--light-blue);
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
        }
        
        .form-file-text {
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .form-file-button {
            display: inline-block;
            background-color: var(--medium-blue);
            color: white;
            padding: 0.375rem 0.75rem;
            margin-left: 8px;
            border-radius: 0.2rem;
        }
        
        .preview-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--baby-pink);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="register-container">
                    <div class="register-header">
                        <h2><i class="bi bi-person-plus me-2"></i>Create Account</h2>
                        <p class="text-light mb-0 mt-2">Welcome to our Ticketing System :)</p>
                    </div>
                    
                    <div class="register-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="error-message">
                                <i class="bi bi-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                                <div class="text-center mt-3">
                                    <a href="login.php" class="btn btn-custom-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Proceed to Login
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label"><i class="bi bi-person me-2"></i>Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label"><i class="bi bi-lock me-2"></i>Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                </div>
                                
                                <div class="preview-container">
                                    <img id="imagePreview" class="profile-preview" alt="Profile Preview">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label"><i class="bi bi-image me-2"></i>Profile Picture (Optional)</label>
                                    <div class="form-file">
                                        <input type="file" class="form-file-input" id="profile_image" name="profile_image" accept="image/*">
                                        <label class="form-file-label" for="profile_image">
                                            <span class="form-file-text" id="file-name">Choose file...</span>
                                            <span class="form-file-button">Browse</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-custom-primary">
                                    <i class="bi bi-person-plus me-2"></i>Register
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="login-link">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('profile_image').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : 'Choose file...';
            document.getElementById('file-name').textContent = fileName;
            
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'inline-block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>