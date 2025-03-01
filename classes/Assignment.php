<?php
require_once 'config/database.php';

class Assignment {
    private $conn;

    public function __construct() {
        $this->conn = connectDB();
    }

    // Create a new assignment
    public function create($title, $description, $course_id, $due_date, $file_path = null) {
        $query = "INSERT INTO assignments (title, description, course_id, due_date, file_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssiss", $title, $description, $course_id, $due_date, $file_path);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // Get assignments by course ID
    public function getAssignmentsByCourse($course_id) {
        $query = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = array();
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $assignments;
    }

    // Get assignment by ID
    public function getAssignmentById($assignment_id) {
        $query = "SELECT a.*, c.title as course_title 
                 FROM assignments a 
                 JOIN courses c ON a.course_id = c.id 
                 WHERE a.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Submit an assignment
    public function submitAssignment($assignment_id, $student_id, $file_path) {
        // Check if already submitted
        if ($this->isSubmitted($assignment_id, $student_id)) {
            // Update existing submission
            $query = "UPDATE submissions SET file_path = ?, submission_date = CURRENT_TIMESTAMP WHERE assignment_id = ? AND student_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sii", $file_path, $assignment_id, $student_id);
        } else {
            // Create new submission
            $query = "INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iis", $assignment_id, $student_id, $file_path);
        }
        
        return $stmt->execute();
    }

    // Check if a student has submitted an assignment
    public function isSubmitted($assignment_id, $student_id) {
        $query = "SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $assignment_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    // Get submission for a student
    public function getSubmission($assignment_id, $student_id) {
        $query = "SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $assignment_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Get all submissions for an assignment
    public function getSubmissionsByAssignment($assignment_id) {
        $query = "SELECT s.*, u.name as student_name 
                 FROM submissions s 
                 JOIN users u ON s.student_id = u.id 
                 WHERE s.assignment_id = ? 
                 ORDER BY s.submission_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = array();
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        
        return $submissions;
    }

    // Grade a submission
    public function gradeSubmission($assignment_id, $student_id, $grade, $feedback = null) {
        $query = "UPDATE submissions SET grade = ?, feedback = ? WHERE assignment_id = ? AND student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("dsii", $grade, $feedback, $assignment_id, $student_id);
        
        return $stmt->execute();
    }

    // Delete an assignment
    public function deleteAssignment($assignment_id) {
        $query = "DELETE FROM assignments WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $assignment_id);
        
        return $stmt->execute();
    }
}
?> 