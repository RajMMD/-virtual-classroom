<?php
require_once 'config/database.php';
require_once 'classes/User.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test credentials
$test_email = isset($_GET['email']) ? $_GET['email'] : 'test@example.com';
$test_password = isset($_GET['password']) ? $_GET['password'] : 'password123';

echo "<h2>Login Test</h2>";
echo "Testing login with:<br>";
echo "Email: " . htmlspecialchars($test_email) . "<br>";
echo "Password: " . htmlspecialchars($test_password) . "<br><br>";

// Check database connection
try {
    $conn = connectDB();
    echo "✅ Database connection successful!<br><br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br><br>";
    exit;
}

// Check if email exists in database
$query = "SELECT id, email FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Email found in database<br><br>";
    
    // Get user data
    $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    echo "User details:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Name: " . htmlspecialchars($user['name']) . "<br>";
    echo "Email: " . htmlspecialchars($user['email']) . "<br>";
    echo "Role: " . htmlspecialchars($user['role']) . "<br>";
    echo "Password hash (first 30 chars): " . substr($user['password'], 0, 30) . "...<br><br>";
    
    // Test password verification
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Password verification successful!<br>";
        echo "Login would be successful with these credentials.<br><br>";
    } else {
        echo "❌ Password verification failed!<br>";
        echo "The provided password does not match the stored hash.<br><br>";
        
        // Additional debugging
        echo "Password hash info:<br>";
        $hash_info = password_get_info($user['password']);
        echo "<pre>";
        print_r($hash_info);
        echo "</pre>";
        
        echo "Try these solutions:<br>";
        echo "1. Make sure you're entering the correct password<br>";
        echo "2. If you've forgotten your password, you may need to reset it<br>";
        echo "3. If this is a test account, try re-registering with the same email<br><br>";
    }
} else {
    echo "❌ Email not found in database<br>";
    echo "No user with email '" . htmlspecialchars($test_email) . "' exists.<br><br>";
    
    echo "Try these solutions:<br>";
    echo "1. Make sure you're entering the correct email address<br>";
    echo "2. If you haven't registered yet, please register first<br><br>";
}

// Close connection
$conn->close();

echo "<p>To test with different credentials, use: <code>test_login.php?email=your_email&password=your_password</code></p>";
?> 