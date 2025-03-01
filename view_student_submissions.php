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

// Check if course ID and user ID are provided
if (!isset($_GET['course_id']) || !isset($_GET['user_id'])) {
    $_SESSION['message'] = 'Invalid request. Missing course or student information.';
    $_SESSION['message_type'] = 'error';
    header('Location: teacher_progress.php');
    exit();
}

// Include necessary classes
require_once 'classes/User.php';
require_once 'classes/Course.php';
require_once 'classes/Assignment.php';
require_once 'classes/Progress.php';

// Get parameters
$course_id = intval($_GET['course_id']);
$student_id = intval($_GET['user_id']);
$teacher_id = $_SESSION['user_id'];

// Create instances
$courseObj = new Course();
$userObj = new User();
$assignmentObj = new Assignment();
$progressObj = new Progress();

// Verify that the teacher teaches this course
$course = $courseObj->getCourseById($course_id);
if (!$course || $course['teacher_id'] != $teacher_id) {
    $_SESSION['message'] = 'You do not have permission to view this course.';
    $_SESSION['message_type'] = 'error';
    header('Location: teacher_progress.php');
    exit();
}

// Verify that the student is enrolled in this course
if (!$courseObj->isEnrolled($student_id, $course_id)) {
    $_SESSION['message'] = 'This student is not enrolled in this course.';
    $_SESSION['message_type'] = 'error';
    header('Location: teacher_progress.php?course_id=' . $course_id);
    exit();
}

// Get student information
$student = $userObj->getUserById($student_id);
if (!$student) {
    $_SESSION['message'] = 'Student not found.';
    $_SESSION['message_type'] = 'error';
    header('Location: teacher_progress.php?course_id=' . $course_id);
    exit();
}

// Get student progress for this course
$progress = $progressObj->getStudentCourseProgress($student_id, $course_id);

// Get all assignments for this course
$assignments = $assignmentObj->getAssignmentsByCourse($course_id);

// Get all submissions for this student in this course
$submissions = [];
foreach ($assignments as $assignment) {
    $submission = $assignmentObj->getSubmissionByUserAndAssignment($student_id, $assignment['id']);
    
    // Add assignment details to submission
    if ($submission) {
        $submission['assignment_title'] = $assignment['title'];
        $submission['assignment_description'] = $assignment['description'];
        $submission['due_date'] = $assignment['due_date'];
        $submission['max_points'] = $assignment['max_points'];
    } else {
        // Create a placeholder for assignments without submissions
        $submission = [
            'id' => null,
            'assignment_id' => $assignment['id'],
            'assignment_title' => $assignment['title'],
            'assignment_description' => $assignment['description'],
            'due_date' => $assignment['due_date'],
            'max_points' => $assignment['max_points'],
            'status' => 'not_submitted',
            'grade' => null,
            'feedback' => null,
            'submission_date' => null
        ];
    }
    
    $submissions[] = $submission;
}

// Include header
include 'includes/header.php';
?>

<div class="student-submissions-page">
    <div class="page-header">
        <h1>Student Submissions</h1>
        <div class="breadcrumbs">
            <a href="teacher_progress.php">Courses</a> &gt; 
            <a href="teacher_progress.php?course_id=<?php echo $course_id; ?>"><?php echo htmlspecialchars($course['title']); ?></a> &gt; 
            <?php echo htmlspecialchars($student['name']); ?>
        </div>
    </div>
    
    <div class="student-info-card">
        <div class="student-avatar">
            <?php if (!empty($student['avatar'])): ?>
                <img src="uploads/avatars/<?php echo $student['avatar']; ?>" alt="<?php echo htmlspecialchars($student['name']); ?>">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="student-details">
            <h2><?php echo htmlspecialchars($student['name']); ?></h2>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></p>
            <?php if (!empty($student['bio'])): ?>
                <p class="student-bio"><?php echo htmlspecialchars($student['bio']); ?></p>
            <?php endif; ?>
        </div>
        <div class="student-progress">
            <h3>Course Progress</h3>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $progress['percentage']; ?>%;">
                    <span><?php echo $progress['percentage']; ?>%</span>
                </div>
            </div>
            <div class="progress-stats">
                <div class="stat">
                    <span class="stat-label">Assignments Completed:</span>
                    <span class="stat-value"><?php echo $progress['completed_assignments']; ?> / <?php echo $progress['total_assignments']; ?></span>
                </div>
                <div class="stat">
                    <span class="stat-label">Average Grade:</span>
                    <span class="stat-value"><?php echo $progress['grade_average']; ?>/100</span>
                </div>
            </div>
        </div>
    </div>
    
    <h2>Assignments & Submissions</h2>
    
    <?php if (count($submissions) > 0): ?>
        <div class="submissions-list">
            <?php foreach ($submissions as $submission): ?>
                <div class="submission-card">
                    <div class="submission-header">
                        <h3><?php echo htmlspecialchars($submission['assignment_title']); ?></h3>
                        <div class="submission-status <?php echo $submission['status']; ?>">
                            <?php 
                                switch ($submission['status']) {
                                    case 'submitted':
                                        echo '<i class="fas fa-clock"></i> Pending Review';
                                        break;
                                    case 'graded':
                                        echo '<i class="fas fa-check-circle"></i> Graded';
                                        break;
                                    case 'not_submitted':
                                        echo '<i class="fas fa-times-circle"></i> Not Submitted';
                                        break;
                                    default:
                                        echo htmlspecialchars($submission['status']);
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="submission-details">
                        <div class="detail">
                            <span class="detail-label">Due Date:</span>
                            <span class="detail-value"><?php echo date('M j, Y, g:i a', strtotime($submission['due_date'])); ?></span>
                        </div>
                        
                        <?php if ($submission['submission_date']): ?>
                            <div class="detail">
                                <span class="detail-label">Submitted:</span>
                                <span class="detail-value"><?php echo date('M j, Y, g:i a', strtotime($submission['submission_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($submission['status'] === 'graded'): ?>
                            <div class="detail">
                                <span class="detail-label">Grade:</span>
                                <span class="detail-value"><?php echo $submission['grade']; ?> / <?php echo $submission['max_points']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($submission['status'] !== 'not_submitted'): ?>
                        <div class="submission-actions">
                            <a href="view_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Submission
                            </a>
                            
                            <?php if ($submission['status'] === 'submitted'): ?>
                                <a href="grade_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-check"></i> Grade Submission
                                </a>
                            <?php elseif ($submission['status'] === 'graded'): ?>
                                <a href="grade_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Update Grade
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="submission-description">
                            <p><?php echo htmlspecialchars(substr($submission['assignment_description'], 0, 200)) . '...'; ?></p>
                            <a href="view_assignment.php?id=<?php echo $submission['assignment_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-file-alt"></i> View Assignment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert info">
            <p>No assignments have been created for this course yet.</p>
        </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="teacher_progress.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Course Progress
        </a>
    </div>
</div>

<style>
    .student-submissions-page {
        padding: 20px 0;
    }
    
    .page-header {
        margin-bottom: 20px;
    }
    
    .breadcrumbs {
        margin-top: 5px;
        color: var(--text-secondary);
    }
    
    .breadcrumbs a {
        color: var(--primary);
        text-decoration: none;
    }
    
    .breadcrumbs a:hover {
        text-decoration: underline;
    }
    
    .student-info-card {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 20px;
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .student-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
    }
    
    .student-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--bg-secondary);
        color: var(--text-secondary);
        font-size: 2rem;
    }
    
    .student-details h2 {
        margin-top: 0;
        margin-bottom: 10px;
        color: var(--primary);
    }
    
    .student-details p {
        margin: 5px 0;
    }
    
    .student-bio {
        margin-top: 10px;
        font-style: italic;
        color: var(--text-secondary);
    }
    
    .student-progress {
        min-width: 250px;
    }
    
    .student-progress h3 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .progress-bar-container {
        background-color: var(--bg-secondary);
        border-radius: 4px;
        height: 24px;
        margin: 15px 0;
        overflow: hidden;
    }
    
    .progress-bar {
        background-color: var(--primary);
        height: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        transition: width 0.5s ease;
        min-width: 30px;
    }
    
    .progress-stats {
        margin-top: 10px;
    }
    
    .stat {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .stat-label {
        font-weight: bold;
        color: var(--text-secondary);
    }
    
    .stat-value {
        font-weight: bold;
    }
    
    .submissions-list {
        display: grid;
        gap: 20px;
        margin-top: 20px;
    }
    
    .submission-card {
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .submission-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .submission-header h3 {
        margin: 0;
        color: var(--primary);
    }
    
    .submission-status {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.875rem;
    }
    
    .submission-status.submitted {
        background-color: var(--warning-light);
        color: var(--warning);
    }
    
    .submission-status.graded {
        background-color: var(--success-light);
        color: var(--success);
    }
    
    .submission-status.not_submitted {
        background-color: var(--danger-light);
        color: var(--danger);
    }
    
    .submission-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .detail {
        display: flex;
        flex-direction: column;
    }
    
    .detail-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .detail-value {
        font-weight: bold;
    }
    
    .submission-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .submission-description {
        margin-top: 15px;
        color: var(--text-secondary);
    }
    
    .action-buttons {
        margin-top: 30px;
    }
    
    @media (max-width: 768px) {
        .student-info-card {
            grid-template-columns: 1fr;
        }
        
        .student-avatar {
            margin: 0 auto;
        }
        
        .student-details {
            text-align: center;
        }
        
        .submission-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .submission-actions {
            flex-direction: column;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
 