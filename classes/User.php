<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $id;
    private $name;
    private $email;
    private $role;

    public function __construct() {
        $this->conn = connectDB();
    }

    // Register a new user
    public function register($name, $email, $password, $role) {
        // Check if email already exists
        if ($this->emailExists($email)) {
            return false;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the query
        $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

        // Execute the query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Login user
    public function login($email, $password) {
        // Check if email exists
        if (!$this->emailExists($email)) {
            return false;
        }

        // Get user data
        $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set user properties
            $this->id = $user['id'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->role = $user['role'];

            // Start session
            session_start();
            $_SESSION['user_id'] = $this->id;
            $_SESSION['user_name'] = $this->name;
            $_SESSION['user_email'] = $this->email;
            $_SESSION['user_role'] = $this->role;

            return true;
        }

        return false;
    }

    // Check if email exists
    private function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT id, name, email, role FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }

    // Logout user
    public static function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        return true;
    }

    // Check if user is a teacher
    public static function isTeacher() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'teacher';
    }

    // Check if user is a student
    public static function isStudent() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'student';
    }
}
?> 