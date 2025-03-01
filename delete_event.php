<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if event ID is provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid event ID']);
    exit();
}

// Include necessary classes
require_once 'classes/Calendar.php';
require_once 'classes/User.php';

// Get event ID
$event_id = intval($_POST['event_id']);

// Get user ID and role from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Create calendar instance
$calendarObj = new Calendar();

// Get event details to check permissions
$event = $calendarObj->getEventById($event_id);

// Check if event exists
if (!$event) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Event not found']);
    exit();
}

// Check if user has permission to delete this event
$has_permission = false;

if ($user_role === 'teacher') {
    // Teachers can delete events they created or events for courses they teach
    if ($event['created_by'] == $user_id || $event['teacher_id'] == $user_id) {
        $has_permission = true;
    }
} else {
    // Students can only delete events they created
    if ($event['created_by'] == $user_id) {
        $has_permission = true;
    }
}

if (!$has_permission) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

// Delete the event
$result = $calendarObj->deleteEvent($event_id);

if ($result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to delete event']);
}
exit(); 