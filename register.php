<?php
include_once 'includes/header.php';

if (User::isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    // Validate form data
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($role) || !in_array($role, ['student', 'teacher'])) {
        $errors[] = 'Please select a valid role';
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        $user = new User();
        
        if ($user->register($name, $email, $password, $role)) {
            // Set success message
            $_SESSION['message'] = 'Registration successful! You can now login.';
            $_SESSION['message_type'] = 'success';
            
            // Redirect to login page
            header('Location: login.php');
            exit();
        } else {
            $errors[] = 'Email already exists or registration failed';
        }
    }
}
?>

<div class="form-container">
    <h2>Register</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Role</label>
            <div class="role-options">
                <label class="role-option">
                    <input type="radio" name="role" value="student" <?php echo (isset($role) && $role === 'student') ? 'checked' : ''; ?> required>
                    <span>Student</span>
                </label>
                <label class="role-option">
                    <input type="radio" name="role" value="teacher" <?php echo (isset($role) && $role === 'teacher') ? 'checked' : ''; ?> required>
                    <span>Teacher</span>
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Register</button>
        </div>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">Already have an account? <a href="login.php">Login</a></p>
</div>

<style>
    .role-options {
        display: flex;
        gap: 20px;
        margin-top: 10px;
    }
    
    .role-option {
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    
    .role-option input {
        margin-right: 8px;
    }
</style>

<?php include_once 'includes/footer.php'; ?> 