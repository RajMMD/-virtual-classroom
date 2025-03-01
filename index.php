<?php
// Include header
include_once 'includes/header.php';
?>

<div class="hero">
    <h2>Welcome to Virtual Classroom</h2>
    <p>A simple and effective platform for online learning</p>
    
    <?php if (!User::isLoggedIn()): ?>
        <div class="cta-buttons">
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn btn-secondary">Register</a>
        </div>
    <?php else: ?>
        <div class="cta-buttons">
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feature-card">
        <i class="fas fa-user-graduate fa-3x"></i>
        <h3>Student Features</h3>
        <ul>
            <li>Browse and enroll in courses</li>
            <li>Access course materials</li>
            <li>Submit assignments</li>
            <li>Track progress</li>
        </ul>
    </div>
    
    <div class="feature-card">
        <i class="fas fa-chalkboard-teacher fa-3x"></i>
        <h3>Teacher Features</h3>
        <ul>
            <li>Create and manage courses</li>
            <li>Upload course materials</li>
            <li>Create assignments</li>
            <li>Grade student submissions</li>
        </ul>
    </div>
    
    <div class="feature-card">
        <i class="fas fa-laptop-code fa-3x"></i>
        <h3>Platform Benefits</h3>
        <ul>
            <li>Easy to use interface</li>
            <li>Responsive design</li>
            <li>Secure authentication</li>
            <li>Efficient course management</li>
        </ul>
    </div>
</div>

<style>
    .hero {
        text-align: center;
        padding: 60px 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 40px;
    }
    
    .hero h2 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 15px;
    }
    
    .hero p {
        font-size: 1.2rem;
        color: #7f8c8d;
        margin-bottom: 30px;
    }
    
    .cta-buttons {
        margin-top: 20px;
    }
    
    .cta-buttons .btn {
        margin: 0 10px;
        padding: 12px 30px;
        font-size: 1.1rem;
    }
    
    .features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .feature-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 30px;
        text-align: center;
        transition: transform 0.3s;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
    }
    
    .feature-card i {
        color: #3498db;
        margin-bottom: 20px;
    }
    
    .feature-card h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #2c3e50;
    }
    
    .feature-card ul {
        text-align: left;
        padding-left: 20px;
        list-style-type: disc;
    }
    
    .feature-card ul li {
        margin-bottom: 8px;
        color: #555;
    }
    
    @media (max-width: 768px) {
        .hero h2 {
            font-size: 2rem;
        }
        
        .hero p {
            font-size: 1rem;
        }
        
        .cta-buttons .btn {
            display: block;
            margin: 10px auto;
            width: 80%;
        }
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?> 