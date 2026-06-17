<?php
session_start();

$loginError = '';

// If already logged in, you can optionally redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $loginError = 'Please enter both email and password.';
    } else {
        $conn = new mysqli('localhost', 'root', '', 'samlina');
        if ($conn->connect_error) {
            $loginError = 'Database connection failed.';
        } else {
            $stmt = $conn->prepare('SELECT id, name, email, password FROM admin WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    // Verify password (password should be hashed using password_hash on registration)
                    if (password_verify($password, $row['password'])) {
                        // Successful login
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $row['id'];
                        $_SESSION['admin_name'] = $row['name'];
                        // Redirect to dashboard (adjust path if needed)
                        $stmt->close();
                        $conn->close();
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $loginError = 'Invalid email or password.';
                    }
                } else {
                    $loginError = 'Invalid email or password.';
                }

                $stmt->close();
            } else {
                $loginError = 'Query error.';
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Samlina Global</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card login-card">
            <!-- Logo & Company Name -->
            <div class="auth-header">
                <img src="../logo/logo.png" alt="Samlina Global Logo" class="auth-logo">
                <h1 class="company-name">Samlina Global</h1>
                <p class="company-tagline">Nig Limited - Admin Portal</p>
            </div>


                        <?php if (!empty($loginError)): ?>
                <div style="background:#c0392b;color:#fff;padding:12px 16px;border-radius:6px;margin-bottom:12px;text-align:center;">
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>
            <!-- Login Form -->
            <form id="loginForm" class="auth-form" method="POST" action="">
    
                <h2>Admin Login</h2>
                <p class="form-subtitle">Access your admin dashboard</p>

                <div class="form-group">
                    <label for="loginEmail">Email Address <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="loginEmail" name="email" required placeholder="admin@example.com" autocomplete="email">
                    </div>
                    <span class="error-msg" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPassword" name="password" required placeholder="Enter your password" autocomplete="current-password">
                        <button type="button" class="toggle-password" id="toggleLoginPassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-msg" id="passwordError"></span>
                </div>

                
                <input type="hidden" name="login_submit" value="1">
                <button type="submit" class="auth-btn">Login</button>
            </form>

            <!-- Links -->
            <!-- <div class="auth-footer">
                <p>Don't have an account? <a href="admin_register.php">Register here</a></p>
                <p class="demo-hint"><i class="fas fa-info-circle"></i> Demo: admin@samlina.com / 123456</p>
            </div> -->
        </div>

        <!-- Right side image/info (desktop only) -->
        <div class="auth-side">
            <div class="side-content">
                <h2>Welcome Admin</h2>
                <p>Manage all booking requests, approve or reject vehicle reservations, and oversee logistics operations across Nigeria.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> View all booking requests</li>
                    <li><i class="fas fa-check-circle"></i> Approve/Reject bookings</li>
                    <li><i class="fas fa-check-circle"></i> Manage vehicle availability</li>
                    <li><i class="fas fa-check-circle"></i> Monitor admin team</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <script>

        // Toggle password visibility
        document.getElementById('toggleLoginPassword').addEventListener('click', (e) => {
            e.preventDefault();
            const input = document.getElementById('loginPassword');
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