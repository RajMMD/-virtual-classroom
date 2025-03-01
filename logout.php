<?php
// Include User class
require_once 'classes/User.php';

// Logout user
User::logout();

// Set success message
$_SESSION['message'] = 'You have been logged out successfully.';
$_SESSION['message_type'] = 'info';

// Redirect to login page
header('Location: index.php');
exit();
?> 