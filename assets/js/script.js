// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }

    // File input customization
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        const fileLabel = input.nextElementSibling;
        if (fileLabel && fileLabel.classList.contains('file-label')) {
            input.addEventListener('change', function() {
                if (input.files.length > 0) {
                    fileLabel.textContent = input.files[0].name;
                } else {
                    fileLabel.textContent = 'Choose file';
                }
            });
        }
    });

    // Form validation
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const passwordInput = document.querySelector(button.getAttribute('data-target'));
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    button.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    button.innerHTML = '<i class="fas fa-eye"></i>';
                }
            }
        });
    });
    
    // Dark mode toggle
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const htmlElement = document.documentElement;
    
    // Check for saved theme preference or use user's system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Set initial theme based on saved preference or system preference
    if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches)) {
        htmlElement.setAttribute('data-theme', 'dark');
        if (darkModeToggle) {
            darkModeToggle.checked = true;
        }
    } else {
        htmlElement.setAttribute('data-theme', 'light');
        if (darkModeToggle) {
            darkModeToggle.checked = false;
        }
    }
    
    // Toggle theme when the switch is clicked
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                htmlElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                htmlElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    // Handle theme change based on system preference change
    prefersDarkScheme.addEventListener('change', function(e) {
        if (!localStorage.getItem('theme')) {
            if (e.matches) {
                htmlElement.setAttribute('data-theme', 'dark');
                if (darkModeToggle) {
                    darkModeToggle.checked = true;
                }
            } else {
                htmlElement.setAttribute('data-theme', 'light');
                if (darkModeToggle) {
                    darkModeToggle.checked = false;
                }
            }
        }
    });
}); 