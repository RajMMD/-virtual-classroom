<?php
class Progress {
    private $db;

    public function __construct() {
        $this->db = require_once __DIR__ . '/../database/db_connect.php';
    }

    /**
     * Get student progress for a specific course
     * 
     * @param int $user_id The student ID
     * @param int $course_id The course ID
     * @return array Progress data including assignments completed, total assignments, and percentage
     */
    public function getStudentCourseProgress($user_id, $course_id) {
        // Get total assignments for the course
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_assignments 
            FROM assignments 
            WHERE course_id = ?
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_assignments = $result['total_assignments'];

        // If there are no assignments, return 0% progress
        if ($total_assignments == 0) {
            return [
                'completed_assignments' => 0,
                'total_assignments' => 0,
                'percentage' => 0,
                'grade_average' => 0
            ];
        }

        // Get completed assignments (submissions with grade not null)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as completed_assignments 
            FROM submissions 
            JOIN assignments ON submissions.assignment_id = assignments.id
            WHERE submissions.student_id = ? 
            AND assignments.course_id = ?
            AND submissions.grade IS NOT NULL
        ");
        $stmt->execute([$user_id, $course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $completed_assignments = $result['completed_assignments'];

        // Calculate percentage
        $percentage = ($completed_assignments / $total_assignments) * 100;

        // Get average grade for completed assignments
        $stmt = $this->db->prepare("
            SELECT AVG(submissions.grade) as grade_average 
            FROM submissions 
            JOIN assignments ON submissions.assignment_id = assignments.id
            WHERE submissions.student_id = ? 
            AND assignments.course_id = ?
            AND submissions.grade IS NOT NULL
        ");
        $stmt->execute([$user_id, $course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $grade_average = $result['grade_average'] ? round($result['grade_average'], 1) : 0;

        return [
            'completed_assignments' => $completed_assignments,
            'total_assignments' => $total_assignments,
            'percentage' => round($percentage, 1),
            'grade_average' => $grade_average
        ];
    }

    /**
     * Get overall progress for a student across all enrolled courses
     * 
     * @param int $user_id The student ID
     * @return array Overall progress data
     */
    public function getStudentOverallProgress($user_id) {
        // Get all courses the student is enrolled in
        $stmt = $this->db->prepare("
            SELECT course_id 
            FROM enrollments 
            WHERE student_id = ?
        ");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_courses = count($courses);
        
        if ($total_courses == 0) {
            return [
                'total_courses' => 0,
                'total_assignments' => 0,
                'completed_assignments' => 0,
                'overall_percentage' => 0,
                'overall_grade' => 0,
                'course_progress' => []
            ];
        }

        $total_assignments = 0;
        $completed_assignments = 0;
        $weighted_grade_sum = 0;
        $course_progress = [];

        foreach ($courses as $course) {
            $course_id = $course['course_id'];
            $progress = $this->getStudentCourseProgress($user_id, $course_id);
            
            $total_assignments += $progress['total_assignments'];
            $completed_assignments += $progress['completed_assignments'];
            
            // Only include grade if there are completed assignments
            if ($progress['completed_assignments'] > 0) {
                $weighted_grade_sum += $progress['grade_average'] * $progress['completed_assignments'];
            }
            
            // Get course name
            $stmt = $this->db->prepare("SELECT title FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $progress['course_id'] = $course_id;
            $progress['course_name'] = $course_result['title'];
            
            $course_progress[] = $progress;
        }

        // Calculate overall percentage
        $overall_percentage = $total_assignments > 0 
            ? ($completed_assignments / $total_assignments) * 100 
            : 0;

        // Calculate overall grade
        $overall_grade = $completed_assignments > 0 
            ? $weighted_grade_sum / $completed_assignments 
            : 0;

        return [
            'total_courses' => $total_courses,
            'total_assignments' => $total_assignments,
            'completed_assignments' => $completed_assignments,
            'overall_percentage' => round($overall_percentage, 1),
            'overall_grade' => round($overall_grade, 1),
            'course_progress' => $course_progress
        ];
    }

    /**
     * Get teacher statistics for a specific course
     * 
     * @param int $course_id The course ID
     * @return array Course statistics
     */
    public function getTeacherCourseStats($course_id) {
        // Get total students enrolled
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_students 
            FROM enrollments 
            WHERE course_id = ?
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_students = $result['total_students'];

        // Get total assignments
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_assignments 
            FROM assignments 
            WHERE course_id = ?
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_assignments = $result['total_assignments'];

        // Get total submissions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_submissions 
            FROM submissions 
            JOIN assignments ON submissions.assignment_id = assignments.id
            WHERE assignments.course_id = ?
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_submissions = $result['total_submissions'];

        // Get pending submissions (grade IS NULL)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as pending_submissions 
            FROM submissions 
            JOIN assignments ON submissions.assignment_id = assignments.id
            WHERE assignments.course_id = ?
            AND submissions.grade IS NULL
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pending_submissions = $result['pending_submissions'];

        // Get average grade for the course
        $stmt = $this->db->prepare("
            SELECT AVG(submissions.grade) as average_grade 
            FROM submissions 
            JOIN assignments ON submissions.assignment_id = assignments.id
            WHERE assignments.course_id = ?
            AND submissions.grade IS NOT NULL
        ");
        $stmt->execute([$course_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $average_grade = $result['average_grade'] ? round($result['average_grade'], 1) : 0;

        // Calculate submission rate
        $expected_submissions = $total_students * $total_assignments;
        $submission_rate = $expected_submissions > 0 
            ? ($total_submissions / $expected_submissions) * 100 
            : 0;

        return [
            'total_students' => $total_students,
            'total_assignments' => $total_assignments,
            'total_submissions' => $total_submissions,
            'pending_submissions' => $pending_submissions,
            'average_grade' => $average_grade,
            'submission_rate' => round($submission_rate, 1)
        ];
    }

    /**
     * Get student progress data for a specific course (for teacher view)
     * 
     * @param int $course_id The course ID
     * @return array Student progress data for the course
     */
    public function getStudentProgressForCourse($course_id) {
        // Get all students enrolled in the course
        $stmt = $this->db->prepare("
            SELECT e.student_id, u.name 
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.course_id = ?
            ORDER BY u.name
        ");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $student_progress = [];

        foreach ($students as $student) {
            $progress = $this->getStudentCourseProgress($student['student_id'], $course_id);
            $progress['user_id'] = $student['student_id'];
            $progress['name'] = $student['name'];
            
            $student_progress[] = $progress;
        }

        return $student_progress;
    }
} 