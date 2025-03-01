<?php
include_once 'includes/header.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    $_SESSION['message'] = 'Please login to access this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'Invalid course ID.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get course ID
$course_id = intval($_GET['id']);

// Include necessary classes
require_once 'classes/Course.php';
require_once 'classes/Assignment.php';

// Create instances
$courseObj = new Course();
$assignmentObj = new Assignment();

// Get course details
$course = $courseObj->getCourseById($course_id);

// Check if course exists
if (!$course) {
    $_SESSION['message'] = 'Course not found.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if user is the teacher of this course or is enrolled
$is_teacher = User::isTeacher() && $course['teacher_id'] == $user_id;
$is_enrolled = User::isStudent() && $courseObj->isEnrolled($user_id, $course_id);

// If user is not the teacher and not enrolled, redirect
if (!$is_teacher && !$is_enrolled) {
    $_SESSION['message'] = 'You do not have permission to access this course.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get assignments for this course
$assignments = $assignmentObj->getAssignmentsByCourse($course_id);

// Get enrolled students if user is the teacher
$enrolled_students = $is_teacher ? $courseObj->getEnrolledStudents($course_id) : [];
?>

<div class="course-header">
    <h2><?php echo htmlspecialchars($course['title']); ?></h2>
    <p class="course-teacher">Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?></p>
</div>

<div class="course-actions">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    <?php if ($is_teacher): ?>
        <a href="create_assignment.php?course_id=<?php echo $course_id; ?>" class="btn">Create Assignment</a>
    <?php endif; ?>
    <a href="course_chat.php?id=<?php echo $course_id; ?>" class="btn btn-info">
        <i class="fas fa-comments"></i> Course Chat
    </a>
</div>

<div class="course-section">
    <h3>Course Description</h3>
    <div class="course-description">
        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
    </div>
</div>

<div class="course-section">
    <h3>Assignments</h3>
    
    <?php if (empty($assignments)): ?>
        <p class="empty-message">No assignments available for this course yet.</p>
    <?php else: ?>
        <div class="assignments-list">
            <?php foreach ($assignments as $assignment): ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                        <p class="due-date">Due: <?php echo date('F j, Y, g:i a', strtotime($assignment['due_date'])); ?></p>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                        
                        <?php if ($assignment['file_path']): ?>
                            <p><a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="download-link">
                                <i class="fas fa-download"></i> Download Assignment File
                            </a></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <?php if ($is_teacher): ?>
                            <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" class="btn">View Submissions</a>
                        <?php else: ?>
                            <?php 
                                $submission = $assignmentObj->getSubmission($assignment['id'], $user_id);
                                if ($submission): 
                            ?>
                                <div class="submission-info">
                                    <p>Submitted: <?php echo date('F j, Y, g:i a', strtotime($submission['submission_date'])); ?></p>
                                    <?php if ($submission['grade'] !== null): ?>
                                        <p>Grade: <?php echo $submission['grade']; ?></p>
                                    <?php endif; ?>
                                    <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn">Update Submission</a>
                                </div>
                            <?php else: ?>
                                <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn">Submit Assignment</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_teacher): ?>
<div class="course-section">
    <h3>Enrolled Students (<?php echo count($enrolled_students); ?>)</h3>
    
    <?php if (empty($enrolled_students)): ?>
        <p class="empty-message">No students enrolled in this course yet.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Enrollment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolled_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo date('F j, Y', strtotime($student['enrollment_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
    .course-header {
        margin-bottom: 20px;
    }
    
    .course-actions {
        margin-bottom: 30px;
    }
    
    .course-section {
        margin-bottom: 40px;
    }
    
    .course-description {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .assignments-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .due-date {
        color: #e74c3c;
        font-weight: 600;
        margin-top: 5px;
    }
    
    .download-link {
        display: inline-block;
        margin-top: 10px;
    }
    
    .submission-info {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
        .course-actions .btn {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<?php include_once 'includes/footer.php'; ?> 