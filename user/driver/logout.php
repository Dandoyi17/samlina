<?php
// logout.php - destroy driver session and redirect to login
session_start();

// Unset all session variables
$_SESSION = [];

// If session uses cookies, clear the session cookie
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'],
		$params['secure'], $params['httponly']
	);
}

// Destroy the session
session_destroy();

// Redirect to driver login page
header('Location: drivers_login.php');
exit;
