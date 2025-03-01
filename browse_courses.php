<?php
include_once 'includes/header.php';

// Check if user is logged in and is a student
if (!User::isLoggedIn() || !User::isStudent()) {
    $_SESSION['message'] = 'You do not have permission to access this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit();
}

// Include Course class
require_once 'classes/Course.php';

// Create course instance
$courseObj = new Course();

// Get all courses
$courses = $courseObj->getAllCourses();

// Get user ID from session
$student_id = $_SESSION['user_id'];

// Process enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll']) && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    
    // Check if already enrolled
    if ($courseObj->isEnrolled($student_id, $course_id)) {
        $_SESSION['message'] = 'You are already enrolled in this course.';
        $_SESSION['message_type'] = 'info';
    } else {
        // Enroll student
        if ($courseObj->enrollStudent($student_id, $course_id)) {
            $_SESSION['message'] = 'You have successfully enrolled in the course.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to enroll in the course. Please try again.';
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Redirect to same page to prevent form resubmission
    header('Location: browse_courses.php');
    exit();
}
?>

<h2>Browse Courses</h2>
<p>Discover and enroll in available courses.</p>

<div class="dashboard-section">
    <?php if (empty($courses)): ?>
        <p class="empty-message">No courses available at the moment.</p>
    <?php else: ?>
        <div class="dashboard-grid">
            <?php foreach ($courses as $course): ?>
                <div class="card course-card">
                    <div class="card-header">
                        <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                        <p class="course-teacher">Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                    </div>
                    <div class="card-body">
                        <p class="course-description">
                            <?php 
                                $description = htmlspecialchars($course['description']);
                                echo (strlen($description) > 150) ? substr($description, 0, 150) . '...' : $description;
                            ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <?php if ($courseObj->isEnrolled($student_id, $course['id'])): ?>
                            <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn">View Course</a>
                            <span class="enrolled-badge">Enrolled</span>
                        <?php else: ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="enroll" class="btn">Enroll</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .enrolled-badge {
        display: inline-block;
        background-color: #28a745;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        margin-left: 10px;
    }
</style>

<?php include_once 'includes/footer.php'; ?> 