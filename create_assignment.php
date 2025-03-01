<?php
include_once 'includes/header.php';

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    $_SESSION['message'] = 'You do not have permission to access this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit();
}

// Check if course ID is provided
if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
    $_SESSION['message'] = 'Invalid course ID.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get course ID
$course_id = intval($_GET['course_id']);

// Include necessary classes
require_once 'classes/Course.php';
require_once 'classes/Assignment.php';

// Create instances
$courseObj = new Course();
$assignmentObj = new Assignment();

// Get course details
$course = $courseObj->getCourseById($course_id);

// Check if course exists and user is the teacher
if (!$course || $course['teacher_id'] != $_SESSION['user_id']) {
    $_SESSION['message'] = 'You do not have permission to create assignments for this course.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    
    // Validate form data
    if (empty($title)) {
        $errors[] = 'Assignment title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Assignment description is required';
    }
    
    if (empty($due_date)) {
        $errors[] = 'Due date is required';
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $upload_dir = 'uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['assignment_file']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check file size (limit to 5MB)
        if ($_FILES['assignment_file']['size'] > 5000000) {
            $errors[] = 'File is too large. Maximum size is 5MB.';
        }
        
        // Move uploaded file
        if (empty($errors) && move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        } else {
            $errors[] = 'Failed to upload file.';
        }
    }
    
    // If no errors, create the assignment
    if (empty($errors)) {
        $assignment_id = $assignmentObj->create($title, $description, $course_id, $due_date, $file_path);
        
        if ($assignment_id) {
            // Set success message
            $_SESSION['message'] = 'Assignment created successfully!';
            $_SESSION['message_type'] = 'success';
            
            // Redirect to view course page
            header('Location: view_course.php?id=' . $course_id);
            exit();
        } else {
            $errors[] = 'Failed to create assignment. Please try again.';
        }
    }
}
?>

<div class="form-container">
    <h2>Create New Assignment</h2>
    <p>Course: <?php echo htmlspecialchars($course['title']); ?></p>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?course_id=' . $course_id); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Assignment Title</label>
            <input type="text" name="title" id="title" class="form-control" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Assignment Description</label>
            <textarea name="description" id="description" class="form-control" rows="6" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="<?php echo isset($due_date) ? htmlspecialchars($due_date) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="assignment_file">Assignment File (Optional)</label>
            <input type="file" name="assignment_file" id="assignment_file" class="form-control-file">
            <small class="form-text">Upload instructions, resources, or any files needed for the assignment. Max size: 5MB</small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Create Assignment</button>
            <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
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