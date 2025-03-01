<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include User class for authentication checks
require_once 'classes/User.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Classroom</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php">Virtual Classroom</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <?php if (User::isLoggedIn()): ?>
                        <?php if (User::isTeacher()): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="create_course.php">Create Course</a></li>
                            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
                            <li><a href="teacher_progress.php"><i class="fas fa-chart-bar"></i> Progress</a></li>
                        <?php elseif (User::isStudent()): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="browse_courses.php">Browse Courses</a></li>
                            <li><a href="calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
                            <li><a href="progress.php"><i class="fas fa-chart-bar"></i> Progress</a></li>
                        <?php endif; ?>
                        <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                    <li class="theme-switch-container">
                        <div class="theme-switch">
                            <input type="checkbox" id="dark-mode-toggle" class="theme-switch-input">
                            <label for="dark-mode-toggle" class="theme-switch-label">
                                <span class="theme-switch-inner"></span>
                                <span class="theme-switch-switch"></span>
                            </label>
                        </div>
                        <span class="theme-icon"><i class="fas fa-moon"></i></span>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?> 