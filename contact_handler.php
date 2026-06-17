<?php
session_start();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['contact_submit'])) {
    exit;
}

$name = trim($_POST['contact_name'] ?? '');
$email = trim($_POST['contact_email'] ?? '');
$subject = trim($_POST['contact_subject'] ?? '');
$message = trim($_POST['contact_message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    $_SESSION['enquire_error'] = 'All fields are required';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['enquire_error'] = 'Please enter a valid email address';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'samlina');
if ($conn->connect_error) {
    $_SESSION['enquire_error'] = 'Database connection failed';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Insert using prepared statement
$stmt = $conn->prepare("INSERT INTO enquire (name, email, subject, message) VALUES (?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('ssss', $name, $email, $subject, $message);
    if ($stmt->execute()) {
        // Set success flash
        $_SESSION['enquire_success'] = true;
        $_SESSION['enquire_name'] = $name;
        $stmt->close();
        $conn->close();
        
        // Redirect to prevent resubmit
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    } else {
        $_SESSION['enquire_error'] = 'Failed to send message. Please try again.';
    }
    $stmt->close();
} else {
    $_SESSION['enquire_error'] = 'Database query failed';
}

$conn->close();
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>