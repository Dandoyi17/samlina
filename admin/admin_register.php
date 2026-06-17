<?php
session_start();

$regSuccess = false;
$regError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation
    if (!$name || !$email || !$phone || !$password || !$confirmPassword) {
        $regError = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $regError = "Invalid email address.";
    } elseif (strlen($password) < 6) {
        $regError = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $regError = "Passwords do not match.";
    } else {
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert into DB
        $conn = new mysqli('localhost', 'root', '', 'samlina');
        if ($conn->connect_error) {
            $regError = "Database connection failed.";
        } else {
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $regError = "Email already registered.";
            } else {
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO admin (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $name, $email, $phone, $passwordHash);
                if ($stmt->execute()) {
                    $_SESSION['reg_success'] = true;
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $regError = "Registration failed. Please try again.";
                }
            }
            $stmt->close();
            $conn->close();
        }
    }
}

if (!empty($_SESSION['reg_success'])) {
    $regSuccess = true;
    unset($_SESSION['reg_success']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Samlina Global</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card register-card">
            <!-- Logo & Company Name -->
            <div class="auth-header">
                <img src="../logo/logo.png" alt="Samlina Global Logo" class="auth-logo">
                <h1 class="company-name">Samlina Global</h1>
                <p class="company-tagline">Nig Limited - Admin Portal</p>
            </div>

                        <?php if ($regSuccess): ?>
                <div style="background:#27ae60;color:#fff;padding:15px 20px;border-radius:6px;margin-bottom:15px;text-align:center;">
                    Registration successful! You can now <a href="admin_login.php" style="color:#fff;text-decoration:underline;">login</a>.
                </div>
            <?php elseif (!empty($regError)): ?>
                <div style="background:#c0392b;color:#fff;padding:15px 20px;border-radius:6px;margin-bottom:15px;text-align:center;">
                    <?php echo htmlspecialchars($regError); ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form id="registerForm" class="auth-form" method="POST" action="">
                <h2>Admin Registration</h2>
                <p class="form-subtitle">Create your admin account</p>

                <div class="form-group">
                    <label for="regName">Full Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="regName" name="name" required placeholder="John Doe" autocomplete="name">
                    </div>
                    <span class="error-msg" id="nameError"></span>
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="regEmail" name="email" required placeholder="admin@example.com" autocomplete="email">
                    </div>
                    <span class="error-msg" id="regEmailError"></span>
                </div>

                <div class="form-group">
                    <label for="regPhone">Phone Number <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="regPhone" name="phone" required placeholder="08123917323" autocomplete="tel">
                    </div>
                    <span class="error-msg" id="phoneError"></span>
                </div>

                <div class="form-group">
                    <label for="regPassword">Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="regPassword" name="password" required placeholder="Min. 6 characters" autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleRegPassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-msg" id="passwordError"></span>
                    <small class="hint">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="regConfirmPassword">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="regConfirmPassword" name="confirmPassword" required placeholder="Re-enter password" autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleRegConfirmPassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-msg" id="confirmPasswordError"></span>
                </div>

                <input type="hidden" name="register_submit" value="1">
                <button type="submit" class="auth-btn">Register</button>
            </form>

            <!-- Links -->
            <div class="auth-footer">
                <p>Already have an account? <a href="admin_login.php">Login here</a></p>
            </div>
        </div>

        <!-- Right side info (desktop only) -->
        <div class="auth-side">
            <div class="side-content">
                <h2>Join Our Admin Team</h2>
                <p>Register as an admin to manage Samlina Global's logistics operations and vehicle booking system.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Secure admin access</li>
                    <li><i class="fas fa-check-circle"></i> Real-time booking management</li>
                    <li><i class="fas fa-check-circle"></i> Complete vehicle control</li>
                    <li><i class="fas fa-check-circle"></i> Comprehensive reporting</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- <script src="js/admin.js"></script> -->
   <script>
    // Toggle password visibility
    document.getElementById('toggleRegPassword').addEventListener('click', (e) => {
        e.preventDefault();
        const input = document.getElementById('regPassword');
        const icon = e.currentTarget.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    document.getElementById('toggleRegConfirmPassword').addEventListener('click', (e) => {
        e.preventDefault();
        const input = document.getElementById('regConfirmPassword');
        const icon = e.currentTarget.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>
</body>

</html>