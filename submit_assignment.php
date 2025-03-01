<?php
include_once 'includes/header.php';

// Check if user is logged in and is a student
if (!User::isLoggedIn() || !User::isStudent()) {
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
$student_id = $_SESSION['user_id'];

// Check if student is enrolled in the course
if (!$courseObj->isEnrolled($student_id, $assignment['course_id'])) {
    $_SESSION['message'] = 'You are not enrolled in this course.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Check if assignment is past due date
$is_past_due = strtotime($assignment['due_date']) < time();

// Get existing submission if any
$submission = $assignmentObj->getSubmission($assignment_id, $student_id);

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if past due date and no previous submission
    if ($is_past_due && !$submission) {
        $errors[] = 'This assignment is past its due date and cannot be submitted.';
    } else {
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
            $upload_dir = 'uploads/submissions/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $student_id . '_' . basename($_FILES['submission_file']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check file size (limit to 10MB)
            if ($_FILES['submission_file']['size'] > 10000000) {
                $errors[] = 'File is too large. Maximum size is 10MB.';
            }
            
            // Move uploaded file
            if (empty($errors) && move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
                // Submit assignment
                if ($assignmentObj->submitAssignment($assignment_id, $student_id, $target_file)) {
                    // Set success message
                    $_SESSION['message'] = 'Assignment submitted successfully!';
                    $_SESSION['message_type'] = 'success';
                    
                    // Redirect to view course page
                    header('Location: view_course.php?id=' . $assignment['course_id']);
                    exit();
                } else {
                    $errors[] = 'Failed to submit assignment. Please try again.';
                }
            } else {
                $errors[] = 'Failed to upload file.';
            }
        } else {
            $errors[] = 'Please select a file to upload.';
        }
    }
}
?>

<div class="form-container">
    <h2><?php echo $submission ? 'Update Submission' : 'Submit Assignment'; ?></h2>
    <p>Assignment: <?php echo htmlspecialchars($assignment['title']); ?></p>
    <p>Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
    <p class="due-date">Due: <?php echo date('F j, Y, g:i a', strtotime($assignment['due_date'])); ?></p>
    
    <?php if ($is_past_due && !$submission): ?>
        <div class="alert error">
            <p>This assignment is past its due date and cannot be submitted.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($submission): ?>
            <div class="alert info">
                <p>You have already submitted this assignment. Submitting again will replace your previous submission.</p>
                <p>Submitted: <?php echo date('F j, Y, g:i a', strtotime($submission['submission_date'])); ?></p>
                <?php if ($submission['grade'] !== null): ?>
                    <p>Grade: <?php echo $submission['grade']; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $assignment_id); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="submission_file">Upload Your Submission</label>
                <input type="file" name="submission_file" id="submission_file" class="form-control-file" required>
                <small class="form-text">Upload your completed assignment. Max size: 10MB</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn"><?php echo $submission ? 'Update Submission' : 'Submit Assignment'; ?></button>
                <a href="view_course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
    .due-date {
        color: <?php echo $is_past_due ? '#e74c3c' : '#2c3e50'; ?>;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .form-control-file {
        padding: 10px 0;
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>

<?php include_once 'includes/footer.php'; ?> 