<?php
require_once 'config/database.php';

class Course {
    private $conn;

    public function __construct() {
        $this->conn = connectDB();
    }

    // Create a new course
    public function create($title, $description, $teacher_id) {
        $query = "INSERT INTO courses (title, description, teacher_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $title, $description, $teacher_id);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    // Get all courses
    public function getAllCourses() {
        $query = "SELECT c.*, u.name as teacher_name 
                 FROM courses c 
                 JOIN users u ON c.teacher_id = u.id 
                 ORDER BY c.created_at DESC";
        $result = $this->conn->query($query);
        
        $courses = array();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        return $courses;
    }

    // Get courses by teacher ID
    public function getCoursesByTeacher($teacher_id) {
        $query = "SELECT * FROM courses WHERE teacher_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = array();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        return $courses;
    }

    // Get courses by student ID (enrolled courses)
    public function getCoursesByStudent($student_id) {
        $query = "SELECT c.*, u.name as teacher_name 
                 FROM courses c 
                 JOIN enrollments e ON c.id = e.course_id 
                 JOIN users u ON c.teacher_id = u.id 
                 WHERE e.student_id = ? 
                 ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = array();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        return $courses;
    }

    // Get course by ID
    public function getCourseById($course_id) {
        $query = "SELECT c.*, u.name as teacher_name 
                 FROM courses c 
                 JOIN users u ON c.teacher_id = u.id 
                 WHERE c.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Enroll a student in a course
    public function enrollStudent($student_id, $course_id) {
        // Check if already enrolled
        if ($this->isEnrolled($student_id, $course_id)) {
            return false;
        }
        
        $query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $student_id, $course_id);
        
        return $stmt->execute();
    }

    // Check if a student is enrolled in a course
    public function isEnrolled($student_id, $course_id) {
        $query = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    // Get enrolled students for a course
    public function getEnrolledStudents($course_id) {
        $query = "SELECT u.id, u.name, u.email, e.enrollment_date 
                 FROM users u 
                 JOIN enrollments e ON u.id = e.student_id 
                 WHERE e.course_id = ? 
                 ORDER BY e.enrollment_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = array();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        return $students;
    }

    // Delete a course
    public function deleteCourse($course_id, $teacher_id) {
        // Verify that the teacher owns the course
        $query = "SELECT id FROM courses WHERE id = ? AND teacher_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $course_id, $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return false;
        }
        
        // Delete the course
        $query = "DELETE FROM courses WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $course_id);
        
        return $stmt->execute();
    }
}
?> 