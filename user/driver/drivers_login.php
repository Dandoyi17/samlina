<?php
// drivers_login.php
session_start();
require_once '../../db/config.php';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['driver_id'])) {
    header('Location: drivers_dashboard.php');
    exit;
}

$errors = [];
$identifier = ''; // username | email | driver id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your username/email/driver ID and password.';
    } else {
        // Prepared statement: try to find driver by username OR email OR id OR driver_id
        $sql = "SELECT id, name, username, email, password, status FROM drivers
                WHERE username = ? OR email = ? OR id = ? OR driver_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'Server error: ' . $conn->error;
        } else {
            // bind the identifier for all four placeholders (all as strings)
            $stmt->bind_param('ssss', $identifier, $identifier, $identifier, $identifier);
            $stmt->execute();

            $row = null;
            if (method_exists($stmt, 'get_result')) {
                $res = $stmt->get_result();
                if ($res) {
                    $row = $res->fetch_assoc();
                }
            } else {
                // fallback for environments without mysqlnd
                $stmt->bind_result($id, $name, $username, $email, $hash, $status);
                if ($stmt->fetch()) {
                    $row = [
                        'id' => $id,
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'password' => $hash,
                        'status' => $status
                    ];
                }
            }

            if (empty($row)) {
                $errors[] = 'No account found for that identifier.';
            } else {
                if (empty($row['password'])) {
                    $errors[] = 'This account has no password set. Contact admin.';
                } else {
                    if (password_verify($password, $row['password'])) {
                        // Optional: check driver status (for example, require 'active')
                        // if (isset($row['status']) && $row['status'] !== 'active') {
                        //     $errors[] = 'Account not active. Contact admin.';
                        // } else {
                            // Login success: set session and redirect
                            $_SESSION['driver_id'] = $row['id'];
                            $_SESSION['driver_name'] = $row['name'] ?? $row['username'] ?? '';
                            $_SESSION['driver_logged_in'] = true;

                            // Optional last_login update — only run if column exists
                            $colCheck = $conn->query("SHOW COLUMNS FROM `drivers` LIKE 'last_login'");
                            if ($colCheck && $colCheck->num_rows > 0) {
                                $upd = $conn->prepare("UPDATE drivers SET last_login = NOW() WHERE id = ?");
                                if ($upd) {
                                    $upd->bind_param('i', $row['id']);
                                    $upd->execute();
                                    $upd->close();
                                }
                            }

                            header('Location: drivers_dashboard.php');
                            exit;
                        // }
                    } else {
                        $errors[] = 'Incorrect password.';
                    }
                }
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Driver Login — Samlina Global</title>
    <!-- optional global styles -->
    <link rel="stylesheet" href="../css/drivers_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="driver-login-page">
    <header class="topbar">
        <div class="topbar-left">
            <img class="brand-logo" src="../../logo/logowhite.png" alt="Samlina Global logo">
            <div class="brand-text">
                <h1>Samlina Global</h1>
                <p class="tag">Driver Portal</p>
            </div>
        </div>

        <button id="hamburger" class="hamburger" aria-label="Toggle menu">
      <i class="fas fa-bars"></i>
    </button>

        <nav id="topNav" class="topnav" aria-hidden="true">
            <a href="../../index.php">Home</a>
            <a href="../../admin/admin_login.php">Admin</a>
            <a href="drivers_login.php" class="active">Driver</a>
        </nav>
    </header>

    <main class="login-main">
        <section class="login-left">
            <img src="../../logo/logowhite.png" alt="Samlina Global logo" class="hero-logo">
            <h2>Drive with Samlina</h2>
            <p class="lead">Sign in to see your assigned tasks, accept jobs and update your status. Secure, simple and fast.</p>
            <ul class="benefits">
                <li><i class="fas fa-check-circle"></i> View assigned tasks</li>
                <li><i class="fas fa-check-circle"></i> Accept or decline assignments</li>
                <li><i class="fas fa-check-circle"></i> Track ratings & earnings</li>
            </ul>
        </section>

        <section class="login-right" aria-labelledby="loginTitle">
            <div class="login-box">
                <h2 id="loginTitle">Driver Sign In</h2>
<form id="loginForm" method="post" action="drivers_login.php" novalidate>
    <label for="identifier">Username, Email or Driver ID</label>
    <input id="identifier" name="identifier" type="text" autocomplete="username" required placeholder="username, email or D-1001" value="<?= htmlspecialchars($identifier ?? '') ?>">

    <label for="password">Password</label>
    <div class="password-row" style="display:flex;gap:.5rem;align-items:center;">
        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Your password" style="flex:1;">
        <button type="button" id="togglePassword" class="icon-btn" aria-label="Show password" style="background:transparent;border:0;cursor:pointer;">
            <i class="far fa-eye"></i>
        </button>
    </div>

    <div class="row-between" style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;">
        <label class="checkbox" style="display:flex;align-items:center;gap:.4rem;">
            <input id="remember" type="checkbox" name="remember"> <span>Remember me</span>
        </label>
        <a href="#" class="small muted">Forgot password?</a>
    </div>

    <div class="form-actions" style="margin-top:0.75rem;">
        <button id="loginBtn" class="btn primary" type="submit" aria-live="polite" aria-busy="false">
            <span class="btn-icon" aria-hidden="true"><i class="fas fa-sign-in-alt"></i></span>
            <span class="btn-text">Sign in</span>
        </button>
    </div>

    <div id="loginMessage" role="status" aria-live="polite" class="muted small" style="margin-top:.5rem;">
        <?php if (!empty($errors)): foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; endif; ?>
    </div>
</form>

                <p class="small muted" style="margin-top:12px;">Need help? Contact <a href="mailto:support@samlina.com">support@samlina.com</a></p>
            </div>
        </section>
    </main>

    <footer class="login-footer">
        <p>&copy; <span id="year"></span> Samlina Global Nig Limited</p>
    </footer>

    <script>
    // Toggle password visibility
    document.getElementById('togglePassword')?.addEventListener('click', function(){
        var pwd = document.getElementById('password');
        if (!pwd) return;
        if (pwd.type === 'password') {
            pwd.type = 'text';
            this.querySelector('i')?.classList.replace('far','fas');
        } else {
            pwd.type = 'password';
            this.querySelector('i')?.classList.replace('fas','far');
        }
    });
    </script>
</body>

</html>