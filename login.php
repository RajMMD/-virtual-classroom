<?php
include_once 'includes/header.php';

if (User::isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        $user = new User();
        if ($user->login($email, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}
?>

<div class="form-container">
    <h2>Login</h2>
    
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
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Login</button>
        </div>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">Don't have an account? <a href="register.php">Register</a></p>
</div>

<?php include_once 'includes/footer.php'; ?> 