<?php
include_once 'includes/header.php';

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    $_SESSION['message'] = 'You do not have permission to access this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit();
}

// Check if assignment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'Invalid assignment ID.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get assignment ID
$assignment_id = intval($_GET['id']);

// Include necessary classes
require_once 'classes/Assignment.php';
require_once 'classes/Course.php';

// Create instances
$assignmentObj = new Assignment();
$courseObj = new Course();

// Get assignment details
$assignment = $assignmentObj->getAssignmentById($assignment_id);

// Check if assignment exists
if (!$assignment) {
    $_SESSION['message'] = 'Assignment not found.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get user ID from session
$teacher_id = $_SESSION['user_id'];

// Get course details
$course = $courseObj->getCourseById($assignment['course_id']);

// Check if user is the teacher of this course
if (!$course || $course['teacher_id'] != $teacher_id) {
    $_SESSION['message'] = 'You do not have permission to view submissions for this assignment.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Process grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = intval($_POST['submission_id']);
    $student_id = intval($_POST['student_id']);
    $grade = floatval($_POST['grade']);
    $feedback = trim($_POST['feedback'] ?? '');
    
    // Validate grade
    if ($grade < 0 || $grade > 100) {
        $_SESSION['message'] = 'Grade must be between 0 and 100.';
        $_SESSION['message_type'] = 'error';
    } else {
        // Update grade
        if ($assignmentObj->gradeSubmission($assignment_id, $student_id, $grade, $feedback)) {
            $_SESSION['message'] = 'Submission graded successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to grade submission. Please try again.';
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to same page to prevent form resubmission
    header('Location: view_submissions.php?id=' . $assignment_id);
    exit();
}

// Get all submissions for this assignment
$submissions = $assignmentObj->getSubmissionsByAssignment($assignment_id);
?>

<div class="submissions-header">
    <h2>Submissions for "<?php echo htmlspecialchars($assignment['title']); ?>"</h2>
    <p>Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
    <p>Due Date: <?php echo date('F j, Y, g:i a', strtotime($assignment['due_date'])); ?></p>
</div>

<div class="submissions-actions">
    <a href="view_course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">Back to Course</a>
</div>

<div class="submissions-section">
    <?php if (empty($submissions)): ?>
        <p class="empty-message">No submissions received for this assignment yet.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Submission Date</th>
                        <th>Status</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                            <td><?php echo date('F j, Y, g:i a', strtotime($submission['submission_date'])); ?></td>
                            <td>
                                <?php
                                    $submission_status = 'On Time';
                                    if (strtotime($submission['submission_date']) > strtotime($assignment['due_date'])) {
                                        $submission_status = 'Late';
                                    }
                                    echo $submission_status;
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($submission['grade'] !== null) {
                                        echo $submission['grade'] . '/100';
                                    } else {
                                        echo 'Not Graded';
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" class="btn btn-sm" target="_blank">View Submission</a>
                                <button class="btn btn-sm" onclick="openGradeModal(<?php echo $submission['id']; ?>, <?php echo $submission['student_id']; ?>, '<?php echo htmlspecialchars($submission['student_name']); ?>', <?php echo $submission['grade'] !== null ? $submission['grade'] : 'null'; ?>, '<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>')">Grade</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Grade Modal -->
<div id="gradeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeGradeModal()">&times;</span>
        <h3>Grade Submission</h3>
        <p id="studentName"></p>
        
        <form id="gradeForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $assignment_id); ?>" method="post">
            <input type="hidden" name="submission_id" id="submissionId">
            <input type="hidden" name="student_id" id="studentId">
            
            <div class="form-group">
                <label for="grade">Grade (0-100)</label>
                <input type="number" name="grade" id="grade" class="form-control" min="0" max="100" step="0.1" required>
            </div>
            
            <div class="form-group">
                <label for="feedback">Feedback (Optional)</label>
                <textarea name="feedback" id="feedback" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" name="grade_submission" class="btn">Save Grade</button>
                <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .submissions-header {
        margin-bottom: 20px;
    }
    
    .submissions-actions {
        margin-bottom: 30px;
    }
    
    .submissions-section {
        margin-bottom: 40px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
        margin-right: 5px;
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
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        width: 80%;
        max-width: 600px;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: #333;
    }
</style>

<script>
    // Modal functions
    function openGradeModal(submissionId, studentId, studentName, grade, feedback) {
        document.getElementById('submissionId').value = submissionId;
        document.getElementById('studentId').value = studentId;
        document.getElementById('studentName').textContent = 'Student: ' + studentName;
        document.getElementById('grade').value = grade !== null ? grade : '';
        document.getElementById('feedback').value = feedback;
        document.getElementById('gradeModal').style.display = 'block';
    }
    
    function closeGradeModal() {
        document.getElementById('gradeModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('gradeModal');
        if (event.target == modal) {
            closeGradeModal();
        }
    }
</script>

<?php include_once 'includes/footer.php'; ?> 