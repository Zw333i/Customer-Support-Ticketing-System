<?php
// edit_profile.php
include "config.php";
include "session.php";

$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : "uploads/default.png";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = trim($_POST['full_name']);

    if (!empty($_FILES["profile_image"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->bind_param("si", $new_name, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Profile updated successfully!";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile</title>
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
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
        
        .profile-container {
            background: var(--blue);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--medium-blue);
            width: 100%;
            max-width: 550px;
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
        
        .profile-pic-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--baby-pink);
            margin-bottom: 15px;
        }
        
        .profile-pic-preview {
            display: none;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--baby-pink);
            margin-bottom: 15px;
        }
        
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-container input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <h2><i class="bi bi-person-gear me-2"></i>Edit Profile</h2>
        
        <div class="profile-pic-container">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" id="currentProfilePic" alt="Profile Picture">
            <img src="" class="profile-pic-preview" id="profilePreview" alt="Profile Preview">
        </div>
        
        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label"><i class="bi bi-person me-2"></i>Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label"><i class="bi bi-image me-2"></i>Profile Picture</label>
                <div class="input-group">
                    <input type="file" name="profile_image" class="form-control" id="profileImageInput" accept="image/*">
                    <button class="btn btn-outline-light" type="button" id="clearFileBtn" style="display: none;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="form-text text-light">Recommended: Square image, at least 200x200 pixels</div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                <button type="submit" class="btn btn-custom-primary px-4">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
                <a href="index.php" class="btn btn-custom-secondary px-4">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('currentProfilePic').style.display = 'none';
                    document.getElementById('profilePreview').style.display = 'block';
                    document.getElementById('profilePreview').src = e.target.result;
                    document.getElementById('clearFileBtn').style.display = 'block';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('clearFileBtn').addEventListener('click', function() {
            document.getElementById('profileImageInput').value = '';
            document.getElementById('profilePreview').style.display = 'none';
            document.getElementById('currentProfilePic').style.display = 'block';
            this.style.display = 'none';
        });
    </script>
</body>
</html>