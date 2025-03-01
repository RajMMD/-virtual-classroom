<?php
include_once 'includes/header.php';

// Check if user is logged in
if (!User::isLoggedIn()) {
    $_SESSION['message'] = 'Please login to access the dashboard.';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// Include necessary classes
require_once 'classes/Course.php';
require_once 'classes/Assignment.php';

// Create instances
$courseObj = new Course();
$assignmentObj = new Assignment();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get courses based on user role
if (User::isTeacher()) {
    $courses = $courseObj->getCoursesByTeacher($user_id);
    $dashboard_title = "Teacher Dashboard";
    $empty_message = "You haven't created any courses yet.";
    
    // Get analytics data for teacher
    $total_students = 0;
    $total_assignments = 0;
    $total_submissions = 0;
    $pending_submissions = 0;
    
    foreach ($courses as $course) {
        // Count enrolled students
        $enrolled_students = $courseObj->getEnrolledStudents($course['id']);
        $total_students += count($enrolled_students);
        
        // Count assignments
        $assignments = $assignmentObj->getAssignmentsByCourse($course['id']);
        $total_assignments += count($assignments);
        
        // Count submissions
        foreach ($assignments as $assignment) {
            $submissions = $assignmentObj->getSubmissionsByAssignment($assignment['id']);
            $total_submissions += count($submissions);
            
            // Count pending (ungraded) submissions
            foreach ($submissions as $submission) {
                if ($submission['grade'] === null) {
                    $pending_submissions++;
                }
            }
        }
    }
} else {
    $courses = $courseObj->getCoursesByStudent($user_id);
    $dashboard_title = "Student Dashboard";
    $empty_message = "You haven't enrolled in any courses yet.";
    
    // Get analytics data for student
    $total_courses = count($courses);
    $total_assignments = 0;
    $completed_assignments = 0;
    $pending_assignments = 0;
    $upcoming_assignments = [];
    
    foreach ($courses as $course) {
        // Count assignments
        $assignments = $assignmentObj->getAssignmentsByCourse($course['id']);
        $total_assignments += count($assignments);
        
        foreach ($assignments as $assignment) {
            // Check if submitted
            $submission = $assignmentObj->getSubmission($assignment['id'], $user_id);
            
            if ($submission) {
                $completed_assignments++;
            } else {
                $pending_assignments++;
                
                // Add to upcoming assignments if due date is in the future
                if (strtotime($assignment['due_date']) > time()) {
                    $assignment['course_title'] = $course['title'];
                    $upcoming_assignments[] = $assignment;
                }
            }
        }
    }
    
    // Sort upcoming assignments by due date (closest first)
    usort($upcoming_assignments, function($a, $b) {
        return strtotime($a['due_date']) - strtotime($b['due_date']);
    });
    
    // Limit to 5 upcoming assignments
    $upcoming_assignments = array_slice($upcoming_assignments, 0, 5);
}
?>

<h2><?php echo $dashboard_title; ?></h2>
<p class="welcome-message">Welcome, <?php echo $_SESSION['user_name']; ?>!</p>

<!-- Analytics Section -->
<div class="analytics-section">
    <?php if (User::isTeacher()): ?>
        <div class="analytics-cards">
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo count($courses); ?></h3>
                    <p>Courses</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Students</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $total_assignments; ?></h3>
                    <p>Assignments</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $pending_submissions; ?></h3>
                    <p>Pending Submissions</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="analytics-cards">
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $total_courses; ?></h3>
                    <p>Enrolled Courses</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $total_assignments; ?></h3>
                    <p>Total Assignments</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $completed_assignments; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="analytics-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="analytics-data">
                    <h3><?php echo $pending_assignments; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (User::isTeacher()): ?>
    <div class="dashboard-actions">
        <a href="create_course.php" class="btn">Create New Course</a>
    </div>
<?php else: ?>
    <div class="dashboard-actions">
        <a href="browse_courses.php" class="btn">Browse Courses</a>
    </div>
    
    <!-- Upcoming Assignments Section for Students -->
    <?php if (!empty($upcoming_assignments)): ?>
        <div class="dashboard-section">
            <h3>Upcoming Assignments</h3>
            <div class="upcoming-assignments">
                <?php foreach ($upcoming_assignments as $assignment): ?>
                    <div class="upcoming-assignment-card">
                        <div class="assignment-due">
                            <span class="due-label">Due</span>
                            <span class="due-date"><?php echo date('M d', strtotime($assignment['due_date'])); ?></span>
                            <span class="due-time"><?php echo date('g:i A', strtotime($assignment['due_date'])); ?></span>
                        </div>
                        <div class="assignment-details">
                            <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                            <p class="course-name"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
                            <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm">Submit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="dashboard-section">
    <h3>Your Courses</h3>
    
    <?php if (empty($courses)): ?>
        <p class="empty-message"><?php echo $empty_message; ?></p>
    <?php else: ?>
        <div class="dashboard-grid">
            <?php foreach ($courses as $course): ?>
                <div class="card course-card">
                    <div class="card-header">
                        <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                        <?php if (isset($course['teacher_name'])): ?>
                            <p class="course-teacher">Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                        <?php endif; ?>
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
                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn">View Course</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .welcome-message {
        margin-bottom: 20px;
        font-size: 1.1rem;
    }
    
    .dashboard-actions {
        margin-bottom: 30px;
    }
    
    .dashboard-section {
        margin-bottom: 40px;
    }
    
    .dashboard-section h3 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .empty-message {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        color: #6c757d;
    }
    
    /* Analytics Section Styles */
    .analytics-section {
        margin-bottom: 30px;
    }
    
    .analytics-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .analytics-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        display: flex;
        align-items: center;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .analytics-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .analytics-icon {
        background-color: #e3f2fd;
        color: #1976d2;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.5rem;
    }
    
    .analytics-data h3 {
        font-size: 1.8rem;
        margin: 0;
        color: #333;
    }
    
    .analytics-data p {
        margin: 5px 0 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    /* Upcoming Assignments Styles */
    .upcoming-assignments {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .upcoming-assignment-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        overflow: hidden;
        transition: transform 0.3s;
    }
    
    .upcoming-assignment-card:hover {
        transform: translateY(-3px);
    }
    
    .assignment-due {
        background-color: #f8f9fa;
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        border-right: 1px solid #e0e0e0;
    }
    
    .due-label {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .due-date {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
    }
    
    .due-time {
        font-size: 0.9rem;
        color: #666;
    }
    
    .assignment-details {
        padding: 15px;
        flex-grow: 1;
    }
    
    .assignment-details h4 {
        margin: 0 0 5px;
        font-size: 1.1rem;
    }
    
    .course-name {
        color: #666;
        margin: 0 0 10px;
        font-size: 0.9rem;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
        .analytics-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .upcoming-assignment-card {
            flex-direction: column;
        }
        
        .assignment-due {
            border-right: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px;
            flex-direction: row;
            justify-content: space-between;
            width: 100%;
        }
        
        .due-label, .due-date, .due-time {
            margin: 0 5px;
        }
    }
    
    @media (max-width: 576px) {
        .analytics-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include_once 'includes/footer.php'; ?> 