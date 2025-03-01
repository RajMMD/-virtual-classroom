<?php
include_once 'includes/header.php';

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    $_SESSION['message'] = 'You do not have permission to access this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit();
}

// Include Course class
require_once 'classes/Course.php';

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $teacher_id = $_SESSION['user_id'];
    
    // Validate form data
    if (empty($title)) {
        $errors[] = 'Course title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Course description is required';
    }
    
    // If no errors, create the course
    if (empty($errors)) {
        $courseObj = new Course();
        
        $course_id = $courseObj->create($title, $description, $teacher_id);
        
        if ($course_id) {
            // Set success message
            $_SESSION['message'] = 'Course created successfully!';
            $_SESSION['message_type'] = 'success';
            
            // Redirect to view course page
            header('Location: view_course.php?id=' . $course_id);
            exit();
        } else {
            $errors[] = 'Failed to create course. Please try again.';
        }
    }
}
?>

<div class="form-container">
    <h2>Create New Course</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div class="form-group">
            <label for="title">Course Title</label>
            <input type="text" name="title" id="title" class="form-control" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Course Description</label>
            <textarea name="description" id="description" class="form-control" rows="6" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Create Course</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }
</style>

<?php include_once 'includes/footer.php'; ?> 