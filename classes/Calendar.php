<?php
require_once 'config/database.php';

class Calendar {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    /**
     * Create a new calendar event
     */
    public function createEvent($title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id, $created_by) {
        $query = "INSERT INTO calendar_events (title, description, event_type, start_datetime, end_datetime, location, course_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssii", $title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id, $created_by);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing calendar event
     */
    public function updateEvent($id, $title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id) {
        $query = "UPDATE calendar_events 
                  SET title = ?, description = ?, event_type = ?, start_datetime = ?, end_datetime = ?, location = ?, course_id = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssis", $title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a calendar event
     */
    public function deleteEvent($id) {
        $query = "DELETE FROM calendar_events WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get a single event by ID
     */
    public function getEventById($id) {
        $query = "SELECT e.*, c.title as course_title, u.name as creator_name 
                  FROM calendar_events e 
                  LEFT JOIN courses c ON e.course_id = c.id 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE e.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Get events for a specific course
     */
    public function getEventsByCourse($course_id) {
        $query = "SELECT * FROM calendar_events WHERE course_id = ? ORDER BY start_datetime";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }
    
    /**
     * Get events for a specific user (based on enrolled courses for students, or created courses for teachers)
     */
    public function getEventsByUser($user_id, $user_role, $start_date = null, $end_date = null) {
        if ($user_role === 'teacher') {
            $query = "SELECT e.*, c.title as course_title 
                      FROM calendar_events e 
                      LEFT JOIN courses c ON e.course_id = c.id 
                      WHERE (e.created_by = ? OR c.teacher_id = ?)";
        } else {
            $query = "SELECT e.*, c.title as course_title 
                      FROM calendar_events e 
                      LEFT JOIN courses c ON e.course_id = c.id 
                      LEFT JOIN enrollments en ON c.id = en.course_id 
                      WHERE en.student_id = ?";
        }
        
        // Add date range filter if provided
        if ($start_date && $end_date) {
            $query .= " AND ((e.start_datetime BETWEEN ? AND ?) 
                      OR (e.end_datetime BETWEEN ? AND ?) 
                      OR (e.start_datetime <= ? AND e.end_datetime >= ?))";
        }
        
        $query .= " ORDER BY e.start_datetime";
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_role === 'teacher') {
            if ($start_date && $end_date) {
                $stmt->bind_param("iissssss", $user_id, $user_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
            } else {
                $stmt->bind_param("ii", $user_id, $user_id);
            }
        } else {
            if ($start_date && $end_date) {
                $stmt->bind_param("issssss", $user_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
            } else {
                $stmt->bind_param("i", $user_id);
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }
    
    /**
     * Get events for a specific month
     */
    public function getEventsByMonth($user_id, $user_role, $year, $month) {
        $start_date = date('Y-m-d H:i:s', strtotime($year . '-' . $month . '-01 00:00:00'));
        $end_date = date('Y-m-d H:i:s', strtotime($year . '-' . $month . '-' . date('t', strtotime($start_date)) . ' 23:59:59'));
        
        return $this->getEventsByUser($user_id, $user_role, $start_date, $end_date);
    }
    
    /**
     * Create a reminder for a user
     */
    public function createReminder($user_id, $event_id, $reminder_time) {
        $query = "INSERT INTO user_reminders (user_id, event_id, reminder_time) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iis", $user_id, $event_id, $reminder_time);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a reminder
     */
    public function deleteReminder($id) {
        $query = "DELETE FROM user_reminders WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get reminders for a user
     */
    public function getRemindersByUser($user_id) {
        $query = "SELECT r.*, e.title as event_title, e.start_datetime, e.event_type 
                  FROM user_reminders r 
                  JOIN calendar_events e ON r.event_id = e.id 
                  WHERE r.user_id = ? AND r.is_sent = 0 AND r.reminder_time > NOW() 
                  ORDER BY r.reminder_time";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reminders = [];
        while ($row = $result->fetch_assoc()) {
            $reminders[] = $row;
        }
        
        return $reminders;
    }
    
    /**
     * Mark a reminder as sent
     */
    public function markReminderAsSent($id) {
        $query = "UPDATE user_reminders SET is_sent = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get upcoming events for a user
     */
    public function getUpcomingEvents($user_id, $user_role, $limit = 5) {
        $now = date('Y-m-d H:i:s');
        
        if ($user_role === 'teacher') {
            $query = "SELECT e.*, c.title as course_title 
                      FROM calendar_events e 
                      LEFT JOIN courses c ON e.course_id = c.id 
                      WHERE (e.created_by = ? OR c.teacher_id = ?) 
                      AND e.start_datetime >= ? 
                      ORDER BY e.start_datetime 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iisi", $user_id, $user_id, $now, $limit);
        } else {
            $query = "SELECT e.*, c.title as course_title 
                      FROM calendar_events e 
                      LEFT JOIN courses c ON e.course_id = c.id 
                      LEFT JOIN enrollments en ON c.id = en.course_id 
                      WHERE en.student_id = ? 
                      AND e.start_datetime >= ? 
                      ORDER BY e.start_datetime 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("isi", $user_id, $now, $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }
    
    /**
     * Convert assignments to calendar events
     */
    public function syncAssignmentsToCalendar() {
        // Get all assignments
        $query = "SELECT a.*, c.teacher_id 
                  FROM assignments a 
                  JOIN courses c ON a.course_id = c.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($assignment = $result->fetch_assoc()) {
            // Check if event already exists for this assignment
            $check_query = "SELECT id FROM calendar_events 
                           WHERE event_type = 'assignment' 
                           AND title = ? 
                           AND course_id = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param("si", $assignment['title'], $assignment['course_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // If event doesn't exist, create it
            if ($check_result->num_rows === 0) {
                $title = $assignment['title'] . ' (Due)';
                $description = $assignment['description'];
                $event_type = 'assignment';
                $start_datetime = $assignment['due_date'];
                $end_datetime = $assignment['due_date']; // Same as start for assignments
                $location = '';
                $course_id = $assignment['course_id'];
                $created_by = $assignment['teacher_id'];
                
                $this->createEvent($title, $description, $event_type, $start_datetime, $end_datetime, $location, $course_id, $created_by);
            }
        }
        
        return true;
    }
} 