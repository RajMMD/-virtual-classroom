<?php
include_once 'includes/header.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    $_SESSION['message'] = 'Please login to access the calendar.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Include necessary classes
require_once 'classes/Calendar.php';
require_once 'classes/Course.php';

// Create instances
$calendarObj = new Calendar();
$courseObj = new Course();

// Get user ID and role from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get current year and month, or from query parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Validate year and month
if ($year < 2000 || $year > 2100) {
    $year = date('Y');
}
if ($month < 1 || $month > 12) {
    $month = date('n');
}

// Get events for the current month
$events = $calendarObj->getEventsByMonth($user_id, $user_role, $year, $month);

// Process event creation/update
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_event']) || isset($_POST['update_event'])) {
        // Get form data
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_type = $_POST['event_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '00:00';
        $end_date = $_POST['end_date'] ?? '';
        $end_time = $_POST['end_time'] ?? '00:00';
        $location = trim($_POST['location'] ?? '');
        $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;
        
        // Validate form data
        if (empty($title)) {
            $errors[] = 'Event title is required';
        }
        
        if (empty($event_type)) {
            $errors[] = 'Event type is required';
        }
        
        if (empty($start_date)) {
            $errors[] = 'Start date is required';
        }
        
        if (empty($end_date)) {
            $errors[] = 'End date is required';
        }
        
        // Combine date and time
        $start_datetime = date('Y-m-d H:i:s', strtotime($start_date . ' ' . $start_time));
        $end_datetime = date('Y-m-d H:i:s', strtotime($end_date . ' ' . $end_time));
        
        // Check if end date is after start date
        if (strtotime($end_datetime) < strtotime($start_datetime)) {
            $errors[] = 'End date must be after start date';
        }
        
        // If no errors, create or update event
        if (empty($errors)) {
            if (isset($_POST['update_event']) && $event_id) {
                // Update existing event
                if ($calendarObj->updateEvent($event_id, $title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id)) {
                    $success = 'Event updated successfully!';
                } else {
                    $errors[] = 'Failed to update event. Please try again.';
                }
            } else {
                // Create new event
                if ($calendarObj->createEvent($title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id, $user_id)) {
                    $success = 'Event created successfully!';
                } else {
                    $errors[] = 'Failed to create event. Please try again.';
                }
            }
            
            // Refresh events
            $events = $calendarObj->getEventsByMonth($user_id, $user_role, $year, $month);
        }
    } elseif (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        if ($calendarObj->deleteEvent($event_id)) {
            $success = 'Event deleted successfully!';
        } else {
            $errors[] = 'Failed to delete event. Please try again.';
        }
        
        // Refresh events
        $events = $calendarObj->getEventsByMonth($user_id, $user_role, $year, $month);
    } elseif (isset($_POST['create_reminder']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        $reminder_days = intval($_POST['reminder_days'] ?? 0);
        $reminder_hours = intval($_POST['reminder_hours'] ?? 0);
        
        // Get event details
        $event = $calendarObj->getEventById($event_id);
        
        if ($event) {
            // Calculate reminder time
            $event_time = strtotime($event['start_datetime']);
            $reminder_time = $event_time - ($reminder_days * 86400) - ($reminder_hours * 3600);
            $reminder_datetime = date('Y-m-d H:i:s', $reminder_time);
            
            if ($calendarObj->createReminder($user_id, $event_id, $reminder_datetime)) {
                $success = 'Reminder set successfully!';
            } else {
                $errors[] = 'Failed to set reminder. Please try again.';
            }
        } else {
            $errors[] = 'Event not found.';
        }
    }
}

// Get courses for dropdown
if (User::isTeacher()) {
    $courses = $courseObj->getCoursesByTeacher($user_id);
} else {
    $courses = $courseObj->getCoursesByStudent($user_id);
}

// Sync assignments to calendar
$calendarObj->syncAssignmentsToCalendar();

// Get month name and number of days
$month_name = date('F', mktime(0, 0, 0, $month, 1, $year));
$num_days = date('t', mktime(0, 0, 0, $month, 1, $year));
$first_day_of_month = date('N', mktime(0, 0, 0, $month, 1, $year)); // 1 (Monday) to 7 (Sunday)

// Calculate previous and next month/year
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Organize events by day
$events_by_day = [];
foreach ($events as $event) {
    $day = date('j', strtotime($event['start_datetime']));
    if (!isset($events_by_day[$day])) {
        $events_by_day[$day] = [];
    }
    $events_by_day[$day][] = $event;
}
?>

<div class="calendar-container">
    <h2>Calendar</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="calendar-header">
        <div class="calendar-nav">
            <a href="calendar.php?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>" class="btn btn-sm">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <h3><?php echo $month_name . ' ' . $year; ?></h3>
            <a href="calendar.php?year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>" class="btn btn-sm">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <div class="calendar-actions">
            <button class="btn" id="createEventBtn">Create Event</button>
        </div>
    </div>
    
    <div class="calendar-grid">
        <div class="calendar-day-header">Mon</div>
        <div class="calendar-day-header">Tue</div>
        <div class="calendar-day-header">Wed</div>
        <div class="calendar-day-header">Thu</div>
        <div class="calendar-day-header">Fri</div>
        <div class="calendar-day-header">Sat</div>
        <div class="calendar-day-header">Sun</div>
        
        <?php
        // Add empty cells for days before the first day of the month
        for ($i = 1; $i < $first_day_of_month; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }
        
        // Add cells for each day of the month
        for ($day = 1; $day <= $num_days; $day++) {
            $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
            $day_class = $is_today ? 'calendar-day today' : 'calendar-day';
            
            echo '<div class="' . $day_class . '">';
            echo '<div class="day-number">' . $day . '</div>';
            
            // Display events for this day
            if (isset($events_by_day[$day])) {
                echo '<div class="day-events">';
                foreach ($events_by_day[$day] as $event) {
                    $event_class = 'event-' . $event['event_type'];
                    $start_time = date('g:i A', strtotime($event['start_datetime']));
                    
                    echo '<div class="calendar-event ' . $event_class . '" data-event-id="' . $event['id'] . '">';
                    echo '<div class="event-time">' . $start_time . '</div>';
                    echo '<div class="event-title">' . htmlspecialchars($event['title']) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Add empty cells for days after the last day of the month
        $last_day_of_month = ($first_day_of_month + $num_days - 1) % 7;
        if ($last_day_of_month != 0) {
            for ($i = $last_day_of_month; $i < 7; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
        }
        ?>
    </div>
</div>

<!-- Event Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modalTitle">Create Event</h3>
        
        <form id="eventForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?year=<?php echo $year; ?>&month=<?php echo $month; ?>">
            <input type="hidden" id="event_id" name="event_id">
            
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="event_type">Event Type</label>
                <select id="event_type" name="event_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="class_session">Class Session</option>
                    <option value="exam">Exam</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group half">
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" value="00:00">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                </div>
                
                <div class="form-group half">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="form-control" value="00:00">
                </div>
            </div>
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="course_id">Course (Optional)</label>
                <select id="course_id" name="course_id" class="form-control">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="create_event" id="createEventSubmit" class="btn">Create Event</button>
                <button type="submit" name="update_event" id="updateEventSubmit" class="btn" style="display: none;">Update Event</button>
                <button type="button" id="deleteEventBtn" class="btn btn-danger" style="display: none;">Delete Event</button>
                <button type="button" id="setReminderBtn" class="btn btn-secondary" style="display: none;">Set Reminder</button>
            </div>
        </form>
        
        <!-- Delete Event Form -->
        <form id="deleteEventForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?year=<?php echo $year; ?>&month=<?php echo $month; ?>" style="display: none;">
            <input type="hidden" name="event_id" id="delete_event_id">
            <input type="hidden" name="delete_event" value="1">
        </form>
        
        <!-- Reminder Form -->
        <form id="reminderForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?year=<?php echo $year; ?>&month=<?php echo $month; ?>" style="display: none;">
            <h4>Set Reminder</h4>
            <p>Remind me before the event:</p>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="reminder_days">Days</label>
                    <input type="number" id="reminder_days" name="reminder_days" class="form-control" min="0" value="1">
                </div>
                
                <div class="form-group half">
                    <label for="reminder_hours">Hours</label>
                    <input type="number" id="reminder_hours" name="reminder_hours" class="form-control" min="0" max="23" value="0">
                </div>
            </div>
            
            <input type="hidden" name="event_id" id="reminder_event_id">
            <input type="hidden" name="create_reminder" value="1">
            
            <div class="form-actions">
                <button type="submit" class="btn">Set Reminder</button>
                <button type="button" id="cancelReminderBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .calendar-container {
        margin-bottom: 40px;
    }
    
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .calendar-nav {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .calendar-nav h3 {
        margin: 0;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        margin-bottom: 30px;
    }
    
    .calendar-day-header {
        text-align: center;
        font-weight: 600;
        padding: 10px;
        background-color: var(--secondary-color);
        color: white;
        border-radius: 4px;
    }
    
    .calendar-day {
        min-height: 120px;
        background-color: var(--card-bg);
        border-radius: 4px;
        padding: 10px;
        box-shadow: 0 2px 5px var(--shadow-color);
        position: relative;
    }
    
    .calendar-day.empty {
        background-color: transparent;
        box-shadow: none;
    }
    
    .calendar-day.today {
        border: 2px solid var(--primary-color);
    }
    
    .day-number {
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .day-events {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .calendar-event {
        padding: 5px;
        border-radius: 3px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .calendar-event:hover {
        transform: translateY(-2px);
    }
    
    .event-assignment {
        background-color: rgba(231, 76, 60, 0.2);
        border-left: 3px solid #e74c3c;
    }
    
    .event-class_session {
        background-color: rgba(52, 152, 219, 0.2);
        border-left: 3px solid #3498db;
    }
    
    .event-exam {
        background-color: rgba(155, 89, 182, 0.2);
        border-left: 3px solid #9b59b6;
    }
    
    .event-other {
        background-color: rgba(46, 204, 113, 0.2);
        border-left: 3px solid #2ecc71;
    }
    
    .event-time {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    
    .event-title {
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
        background-color: var(--card-bg);
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px var(--shadow-color);
        width: 80%;
        max-width: 600px;
        position: relative;
    }
    
    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
    }
    
    .form-row {
        display: flex;
        gap: 15px;
    }
    
    .form-group.half {
        flex: 1;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
        .calendar-grid {
            grid-template-columns: repeat(1, 1fr);
        }
        
        .calendar-day-header {
            display: none;
        }
        
        .calendar-day {
            min-height: auto;
            margin-bottom: 10px;
        }
        
        .calendar-day.empty {
            display: none;
        }
        
        .day-number {
            font-size: 1.2rem;
        }
        
        .calendar-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('eventModal');
        const createEventBtn = document.getElementById('createEventBtn');
        const closeBtn = document.querySelector('.close');
        const eventForm = document.getElementById('eventForm');
        const deleteEventForm = document.getElementById('deleteEventForm');
        const reminderForm = document.getElementById('reminderForm');
        const deleteEventBtn = document.getElementById('deleteEventBtn');
        const setReminderBtn = document.getElementById('setReminderBtn');
        const cancelReminderBtn = document.getElementById('cancelReminderBtn');
        const createEventSubmit = document.getElementById('createEventSubmit');
        const updateEventSubmit = document.getElementById('updateEventSubmit');
        const calendarEvents = document.querySelectorAll('.calendar-event');
        
        // Open modal for creating a new event
        createEventBtn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Create Event';
            eventForm.reset();
            document.getElementById('event_id').value = '';
            createEventSubmit.style.display = 'block';
            updateEventSubmit.style.display = 'none';
            deleteEventBtn.style.display = 'none';
            setReminderBtn.style.display = 'none';
            
            // Set default dates to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
            document.getElementById('end_date').value = today;
            
            eventForm.style.display = 'block';
            reminderForm.style.display = 'none';
            modal.style.display = 'block';
        });
        
        // Close modal
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Open modal for viewing/editing an existing event
        calendarEvents.forEach(function(eventElement) {
            eventElement.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                
                // Fetch event details via AJAX
                fetch('get_event.php?id=' + eventId)
                    .then(response => response.json())
                    .then(event => {
                        document.getElementById('modalTitle').textContent = 'Edit Event';
                        document.getElementById('event_id').value = event.id;
                        document.getElementById('title').value = event.title;
                        document.getElementById('description').value = event.description;
                        document.getElementById('event_type').value = event.event_type;
                        
                        // Parse dates and times
                        const startDateTime = new Date(event.start_datetime);
                        const endDateTime = new Date(event.end_datetime);
                        
                        document.getElementById('start_date').value = startDateTime.toISOString().split('T')[0];
                        document.getElementById('start_time').value = startDateTime.toTimeString().slice(0, 5);
                        document.getElementById('end_date').value = endDateTime.toISOString().split('T')[0];
                        document.getElementById('end_time').value = endDateTime.toTimeString().slice(0, 5);
                        
                        document.getElementById('location').value = event.location;
                        document.getElementById('course_id').value = event.course_id || '';
                        
                        // Update form buttons
                        createEventSubmit.style.display = 'none';
                        updateEventSubmit.style.display = 'block';
                        deleteEventBtn.style.display = 'block';
                        setReminderBtn.style.display = 'block';
                        
                        // Set event ID for delete form
                        document.getElementById('delete_event_id').value = event.id;
                        document.getElementById('reminder_event_id').value = event.id;
                        
                        eventForm.style.display = 'block';
                        reminderForm.style.display = 'none';
                        modal.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching event details:', error);
                    });
            });
        });
        
        // Delete event button
        deleteEventBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this event?')) {
                deleteEventForm.submit();
            }
        });
        
        // Set reminder button
        setReminderBtn.addEventListener('click', function() {
            eventForm.style.display = 'none';
            reminderForm.style.display = 'block';
        });
        
        // Cancel reminder button
        cancelReminderBtn.addEventListener('click', function() {
            eventForm.style.display = 'block';
            reminderForm.style.display = 'none';
        });
        
        // Ensure end date is not before start date
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?> 