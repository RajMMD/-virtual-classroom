<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'You must be logged in to view this page.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Include necessary classes
require_once 'classes/User.php';
require_once 'classes/Course.php';
require_once 'classes/Progress.php';

// Get user ID and role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Create progress instance
$progressObj = new Progress();

// Get progress data based on user role
if ($user_role === 'student') {
    // Get overall progress for student
    $overall_progress = $progressObj->getStudentOverallProgress($user_id);
} else {
    // Redirect teachers to course selection page
    header('Location: teacher_progress.php');
    exit();
}

// Include header
include 'includes/header.php';
?>

<div class="progress-page">
    <h1>My Progress</h1>
    
    <div class="progress-overview">
        <div class="progress-card">
            <h3>Overall Progress</h3>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $overall_progress['overall_percentage']; ?>%;">
                    <span><?php echo $overall_progress['overall_percentage']; ?>%</span>
                </div>
            </div>
            <div class="progress-stats">
                <div class="stat">
                    <span class="stat-label">Courses:</span>
                    <span class="stat-value"><?php echo $overall_progress['total_courses']; ?></span>
                </div>
                <div class="stat">
                    <span class="stat-label">Assignments Completed:</span>
                    <span class="stat-value"><?php echo $overall_progress['completed_assignments']; ?> / <?php echo $overall_progress['total_assignments']; ?></span>
                </div>
                <div class="stat">
                    <span class="stat-label">Average Grade:</span>
                    <span class="stat-value"><?php echo $overall_progress['overall_grade']; ?>/100</span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($overall_progress['course_progress']) > 0): ?>
        <h2>Course Progress</h2>
        <div class="course-progress-grid">
            <?php foreach ($overall_progress['course_progress'] as $course): ?>
                <div class="course-progress-card">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $course['percentage']; ?>%;">
                            <span><?php echo $course['percentage']; ?>%</span>
                        </div>
                    </div>
                    <div class="progress-stats">
                        <div class="stat">
                            <span class="stat-label">Assignments Completed:</span>
                            <span class="stat-value"><?php echo $course['completed_assignments']; ?> / <?php echo $course['total_assignments']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Average Grade:</span>
                            <span class="stat-value"><?php echo $course['grade_average']; ?>/100</span>
                        </div>
                    </div>
                    <a href="view_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">View Course</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert info">
            <p>You are not enrolled in any courses yet. <a href="browse_courses.php">Browse courses</a> to get started.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .progress-page {
        padding: 20px 0;
    }
    
    .progress-overview {
        margin-bottom: 30px;
    }
    
    .progress-card {
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
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
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
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
    
    .course-progress-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .course-progress-card {
        background-color: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .course-progress-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .course-progress-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: var(--primary);
    }
    
    .course-progress-card .btn {
        margin-top: 15px;
        width: 100%;
    }
    
    @media (max-width: 768px) {
        .progress-stats {
            grid-template-columns: 1fr;
        }
        
        .course-progress-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 