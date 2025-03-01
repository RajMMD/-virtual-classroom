<?php
include_once 'includes/header.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    $_SESSION['message'] = 'Please login to access your profile.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Include User class
require_once 'classes/User.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create user instance
$userObj = new User();

// Get user details
$user = $userObj->getUserById($user_id);

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['update_profile'])) {
        // Get form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Validate form data
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // If no errors, update profile
        if (empty($errors)) {
            // Connect to database
            $conn = connectDB();
            
            // Check if email already exists (if changed)
            if ($email !== $user['email']) {
                $query = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $email, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $errors[] = 'Email already exists';
                }
            }
            
            if (empty($errors)) {
                // Update profile
                $query = "UPDATE users SET name = ?, email = ?, bio = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssi", $name, $email, $bio, $user_id);
                
                if ($stmt->execute()) {
                    // Update session variables
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    $success = 'Profile updated successfully!';
                    
                    // Refresh user data
                    $user = $userObj->getUserById($user_id);
                } else {
                    $errors[] = 'Failed to update profile. Please try again.';
                }
            }
            
            $conn->close();
        }
    } elseif (isset($_POST['update_password'])) {
        // Get form data
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validate form data
        if (empty($current_password)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, update password
        if (empty($errors)) {
            // Connect to database
            $conn = connectDB();
            
            // Get current password hash
            $query = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            // Verify current password
            if (password_verify($current_password, $user_data['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Password updated successfully!';
                } else {
                    $errors[] = 'Failed to update password. Please try again.';
                }
            } else {
                $errors[] = 'Current password is incorrect';
            }
            
            $conn->close();
        }
    } elseif (isset($_POST['upload_avatar'])) {
        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            // Check file type
            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            }
            
            // Check file size
            if ($_FILES['avatar']['size'] > $max_size) {
                $errors[] = 'File is too large. Maximum size is 2MB.';
            }
            
            if (empty($errors)) {
                $upload_dir = 'uploads/avatars/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $file_name = $user_id . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                    // Connect to database
                    $conn = connectDB();
                    
                    // Update avatar path
                    $query = "UPDATE users SET avatar = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $target_file, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Avatar uploaded successfully!';
                        
                        // Refresh user data
                        $user = $userObj->getUserById($user_id);
                    } else {
                        $errors[] = 'Failed to update avatar. Please try again.';
                    }
                    
                    $conn->close();
                } else {
                    $errors[] = 'Failed to upload file.';
                }
            }
        } else {
            $errors[] = 'Please select a file to upload.';
        }
    }
}
?>

<div class="profile-container">
    <h2>My Profile</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-grid">
        <div class="profile-sidebar">
            <div class="avatar-container">
                <?php if (isset($user['avatar']) && !empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile Avatar" class="profile-avatar">
                <?php else: ?>
                    <div class="default-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" class="avatar-form">
                    <div class="file-input-container">
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg, image/png, image/gif" class="file-input">
                        <label for="avatar" class="file-input-label">Choose File</label>
                    </div>
                    <button type="submit" name="upload_avatar" class="btn btn-sm">Upload Avatar</button>
                </form>
            </div>
            
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="user-role"><?php echo ucfirst($user['role']); ?></p>
                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <?php if (isset($user['bio']) && !empty($user['bio'])): ?>
                    <div class="user-bio">
                        <h4>Bio</h4>
                        <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-tabs">
                <button class="tab-button active" data-tab="edit-profile">Edit Profile</button>
                <button class="tab-button" data-tab="change-password">Change Password</button>
            </div>
            
            <div class="tab-content">
                <div id="edit-profile" class="tab-pane active">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio (Optional)</label>
                            <textarea name="bio" id="bio" class="form-control" rows="5"><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_profile" class="btn">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <div id="change-password" class="tab-pane">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_password" class="btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-container {
        margin-bottom: 40px;
    }
    
    .profile-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        margin-top: 30px;
    }
    
    .profile-sidebar {
        background-color: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 2px 10px var(--shadow-color);
        padding: 20px;
        text-align: center;
    }
    
    .avatar-container {
        margin-bottom: 20px;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
    }
    
    .default-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: #999;
        margin: 0 auto;
    }
    
    .avatar-form {
        margin-top: 15px;
    }
    
    .file-input-container {
        margin-bottom: 10px;
    }
    
    .file-input {
        display: none;
    }
    
    .file-input-label {
        display: inline-block;
        padding: 8px 15px;
        background-color: #f0f0f0;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .file-input-label:hover {
        background-color: #e0e0e0;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    
    .profile-info {
        text-align: left;
        margin-top: 20px;
    }
    
    .profile-info h3 {
        margin-bottom: 5px;
    }
    
    .user-role {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .user-email {
        color: var(--text-muted);
        margin-bottom: 15px;
    }
    
    .user-bio {
        margin-top: 20px;
        text-align: left;
    }
    
    .user-bio h4 {
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .profile-content {
        background-color: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 2px 10px var(--shadow-color);
        padding: 20px;
    }
    
    .profile-tabs {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
    }
    
    .tab-button {
        background: none;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 1rem;
        color: var(--text-color);
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }
    
    .tab-button:hover {
        color: var(--primary-color);
    }
    
    .tab-button.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
        animation: fadeIn 0.5s;
    }
    
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-sidebar {
            margin-bottom: 20px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Add active class to clicked button and corresponding pane
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // File input preview
        const fileInput = document.getElementById('avatar');
        const fileLabel = document.querySelector('.file-input-label');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileLabel.textContent = this.files[0].name;
            } else {
                fileLabel.textContent = 'Choose File';
            }
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?> 