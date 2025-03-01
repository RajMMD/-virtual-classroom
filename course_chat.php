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

// Create course instance
$courseObj = new Course();

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
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

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

// Process new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        // Connect to database
        $conn = connectDB();
        
        // Insert message into database
        $query = "INSERT INTO chat_messages (course_id, user_id, user_name, user_role, message, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisss", $course_id, $user_id, $user_name, $user_role, $message);
        
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header('Location: course_chat.php?id=' . $course_id);
            exit();
        }
        
        $conn->close();
    }
}

// Get chat messages
$conn = connectDB();
$query = "SELECT * FROM chat_messages WHERE course_id = ? ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Reverse messages to show oldest first
$messages = array_reverse($messages);

$conn->close();
?>

<div class="course-header">
    <h2>Course Chat: <?php echo htmlspecialchars($course['title']); ?></h2>
    <p class="course-teacher">Teacher: <?php echo htmlspecialchars($course['teacher_name']); ?></p>
</div>

<div class="course-actions">
    <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Back to Course</a>
</div>

<div class="chat-container">
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)): ?>
            <div class="empty-chat-message">
                <p>No messages yet. Be the first to start the conversation!</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="chat-message <?php echo ($msg['user_id'] == $user_id) ? 'my-message' : ''; ?>">
                    <div class="message-header">
                        <span class="message-sender">
                            <?php echo htmlspecialchars($msg['user_name']); ?>
                            <span class="user-role">(<?php echo ucfirst($msg['user_role']); ?>)</span>
                        </span>
                        <span class="message-time"><?php echo date('M d, g:i a', strtotime($msg['created_at'])); ?></span>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="chat-input">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $course_id); ?>" method="post">
            <textarea name="message" placeholder="Type your message here..." required></textarea>
            <button type="submit" class="btn">Send</button>
        </form>
    </div>
</div>

<style>
    .course-header {
        margin-bottom: 20px;
    }
    
    .course-actions {
        margin-bottom: 20px;
    }
    
    .chat-container {
        background-color: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 2px 10px var(--shadow-color);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 70vh;
        margin-bottom: 30px;
    }
    
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 20px;
    }
    
    .empty-chat-message {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-muted);
        text-align: center;
    }
    
    .chat-message {
        margin-bottom: 15px;
        padding: 10px 15px;
        border-radius: 8px;
        background-color: rgba(0, 0, 0, 0.03);
        max-width: 80%;
    }
    
    .my-message {
        margin-left: auto;
        background-color: rgba(52, 152, 219, 0.1);
        border-left: 3px solid var(--primary-color);
    }
    
    .message-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }
    
    .message-sender {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .user-role {
        font-weight: normal;
        color: var(--text-muted);
        margin-left: 5px;
    }
    
    .message-time {
        color: var(--text-muted);
    }
    
    .message-content {
        line-height: 1.5;
        word-break: break-word;
    }
    
    .chat-input {
        padding: 15px;
        border-top: 1px solid var(--border-color);
    }
    
    .chat-input form {
        display: flex;
        gap: 10px;
    }
    
    .chat-input textarea {
        flex-grow: 1;
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        resize: none;
        height: 60px;
        background-color: var(--card-bg);
        color: var(--text-color);
    }
    
    .chat-input textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .chat-input button {
        align-self: flex-end;
    }
    
    @media (max-width: 768px) {
        .chat-container {
            height: 60vh;
        }
        
        .chat-message {
            max-width: 90%;
        }
    }
</style>

<script>
    // Auto-scroll to bottom of chat on page load
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Focus on textarea
        document.querySelector('.chat-input textarea').focus();
    });
</script>

<?php include_once 'includes/footer.php'; ?> 