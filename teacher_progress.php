<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    $_SESSION['message'] = 'You must be logged in as a teacher to view this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Include necessary classes
require_once 'classes/User.php';
require_once 'classes/Course.php';
require_once 'classes/Progress.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Create course and progress instances
$courseObj = new Course();
$progressObj = new Progress();

// Get all courses taught by the teacher
$courses = $courseObj->getCoursesByTeacher($user_id);

// Check if a specific course is selected
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$course_stats = null;
$student_progress = null;

if ($selected_course_id) {
    // Verify that the teacher teaches this course
    $course_details = $courseObj->getCourseById($selected_course_id);
    
    if (!$course_details || $course_details['teacher_id'] != $user_id) {
        $_SESSION['message'] = 'You do not have permission to view this course.';
        $_SESSION['message_type'] = 'error';
        header('Location: teacher_progress.php');
        exit();
    }
    
    // Get course statistics
    $course_stats = $progressObj->getTeacherCourseStats($selected_course_id);
    
    // Get student progress for the course
    $student_progress = $progressObj->getStudentProgressForCourse($selected_course_id);
}

// Include header
include 'includes/header.php';
?>

<div class="progress-page">
    <h1>Course Progress Analytics</h1>
    
    <div class="course-selector">
        <h2>Select a Course</h2>
        <?php if (count($courses) > 0): ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                    <a href="teacher_progress.php?course_id=<?php echo $course['id']; ?>" 
                       class="course-card <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert info">
                <p>You are not teaching any courses yet. <a href="create_course.php">Create a course</a> to get started.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($selected_course_id && $course_stats): ?>
        <div class="course-analytics">
            <h2>Analytics for: <?php echo htmlspecialchars($course_details['title']); ?></h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <h3>Students</h3>
                        <p class="stat-value"><?php echo $course_stats['total_students']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-content">
                        <h3>Assignments</h3>
                        <p class="stat-value"><?php echo $course_stats['total_assignments']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-content">
                        <h3>Submissions</h3>
                        <p class="stat-value"><?php echo $course_stats['total_submissions']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-content">
                        <h3>Pending</h3>
                        <p class="stat-value"><?php echo $course_stats['pending_submissions']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-content">
                        <h3>Submission Rate</h3>
                        <p class="stat-value"><?php echo $course_stats['submission_rate']; ?>%</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-award"></i></div>
                    <div class="stat-content">
                        <h3>Average Grade</h3>
                        <p class="stat-value"><?php echo $course_stats['average_grade']; ?>/100</p>
                    </div>
                </div>
            </div>
            
            <?php if (count($student_progress) > 0): ?>
                <h2>Student Progress</h2>
                <div class="table-responsive">
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Completed</th>
                                <th>Progress</th>
                                <th>Average Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_progress as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo $student['completed_assignments']; ?> / <?php echo $student['total_assignments']; ?></td>
                                    <td>
                                        <div class="progress-bar-container small">
                                            <div class="progress-bar" style="width: <?php echo $student['percentage']; ?>%;">
                                                <span><?php echo $student['percentage']; ?>%</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $student['grade_average']; ?>/100</td>
                                    <td>
                                        <a href="view_student_submissions.php?course_id=<?php echo $selected_course_id; ?>&user_id=<?php echo $student['user_id']; ?>" 
                                           class="btn btn-sm btn-primary">View Submissions</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert info">
                    <p>No students are enrolled in this course yet.</p>
                </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="view_course.php?id=<?php echo $selected_course_id; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
                <a href="create_assignment.php?course_id=<?php echo $selected_course_id; ?>" class="btn btn-success">
                    <i class="fas fa-plus"></i> Create Assignment
                </a>
            </div>
        </div>
    <?php elseif (count($courses) > 0): ?>
        <div class="alert info">
            <p>Select a course to view progress analytics.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .progress-page {
        padding: 20px 0;
    }
    
    .course-selector {
        margin-bottom: 30px;
    }
    
    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .course-card {
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        text-decoration: none;
        color: var(--text-primary);
        border: 2px solid transparent;
    }
    
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .course-card.selected {
        border-color: var(--primary);
        background-color: var(--bg-secondary);
    }
    
    .course-card h3 {
        margin-top: 0;
        margin-bottom: 10px;
        color: var(--primary);
    }
    
    .course-card p {
        margin: 0;
        color: var(--text-secondary);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0 30px;
    }
    
    .stat-card {
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
    }
    
    .stat-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-right: 15px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--bg-secondary);
        border-radius: 50%;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-content h3 {
        margin: 0 0 5px;
        font-size: 1rem;
        color: var(--text-secondary);
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0;
        color: var(--text-primary);
    }
    
    .progress-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .progress-table th,
    .progress-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    
    .progress-table th {
        background-color: var(--bg-secondary);
        font-weight: bold;
        color: var(--text-secondary);
    }
    
    .progress-table tr:hover {
        background-color: var(--bg-secondary);
    }
    
    .progress-bar-container.small {
        height: 16px;
        margin: 0;
    }
    
    .progress-bar-container.small .progress-bar {
        font-size: 0.75rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .course-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 