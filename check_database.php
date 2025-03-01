<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'virtual_classroom';

echo "<h2>Database Check</h2>";

// Check if MySQL connection works
try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    echo "✅ MySQL connection successful!<br><br>";
} catch (Exception $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    echo "Please make sure MySQL is running in XAMPP.<br><br>";
    exit;
}

// Check if database exists
$result = $conn->query("SHOW DATABASES LIKE '$db_name'");
if ($result->num_rows > 0) {
    echo "✅ Database '$db_name' exists!<br><br>";
    
    // Connect to the database
    $conn->select_db($db_name);
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists!<br><br>";
        
        // Check users table structure
        $result = $conn->query("DESCRIBE users");
        echo "Users table structure:<br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . ($value === NULL ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Check if any users exist
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        $user_count = $row['count'];
        
        if ($user_count > 0) {
            echo "✅ Found $user_count users in the database<br><br>";
            
            // Show sample user data (without full password)
            $result = $conn->query("SELECT id, name, email, role, LEFT(password, 10) as password_preview FROM users LIMIT 5");
            echo "Sample users (password preview shows only first 10 characters):<br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Password Preview</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br>";
        } else {
            echo "❌ No users found in the database!<br>";
            echo "You need to register at least one user.<br><br>";
        }
    } else {
        echo "❌ Users table does not exist!<br>";
        echo "The database structure may not be properly initialized.<br><br>";
    }
} else {
    echo "❌ Database '$db_name' does not exist!<br>";
    echo "Please create the database and import the schema.<br><br>";
}

// Close connection
$conn->close();

echo "<h3>Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li>Make sure XAMPP is running with both Apache and MySQL services started</li>";
echo "<li>If the database doesn't exist, create it in phpMyAdmin</li>";
echo "<li>If tables don't exist, import the database schema from database/virtual_classroom.sql</li>";
echo "<li>If no users exist, register a new user through the registration page</li>";
echo "<li>If you're having login issues, try the test_login.php script with your credentials</li>";
echo "</ol>";
?> 