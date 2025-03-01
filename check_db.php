<?php
require_once 'config/database.php';

// Check database connection
$conn = connectDB();
echo "Database connection successful!<br>";

// Check if users table exists
$query = "SHOW TABLES LIKE 'users'";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    echo "Users table exists!<br>";
    
    // Check users table structure
    $query = "DESCRIBE users";
    $result = $conn->query($query);
    echo "Users table structure:<br>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Check if any users exist
    $query = "SELECT id, name, email, role, LEFT(password, 20) as password_preview FROM users";
    $result = $conn->query($query);
    echo "Number of users: " . $result->num_rows . "<br>";
    if ($result->num_rows > 0) {
        echo "User records (with password preview):<br>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    }
} else {
    echo "Users table does not exist!<br>";
}

// Close connection
$conn->close();
?> 