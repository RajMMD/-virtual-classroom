<?php
// This script syncs assignments to the calendar
// It can be run manually or via a cron job

// Include necessary classes
require_once 'classes/Calendar.php';
require_once 'classes/Assignment.php';
require_once 'classes/Course.php';

// Create calendar instance
$calendarObj = new Calendar();

// Sync assignments to calendar
$result = $calendarObj->syncAssignmentsToCalendar();

// Output result
if (php_sapi_name() === 'cli') {
    // Command line output
    echo "Sync completed. " . $result . " assignments synced to calendar.\n";
} else {
    // Web output
    echo "<p>Sync completed. " . $result . " assignments synced to calendar.</p>";
    echo "<p><a href='calendar.php'>Return to Calendar</a></p>";
} 