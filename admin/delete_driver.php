<?php
// admin/delete_driver.php
// Handles bulk and single driver deletion

session_start();
require_once __DIR__ . '/../db/config.php';

// Check if user is logged in as admin (optional security check)
// Uncomment if you have admin session validation:
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: admin_login.php');
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: delete.php');
    exit;
}

$delete_ids = trim($_POST['delete_ids'] ?? '');
if ($delete_ids === '') {
    $_SESSION['error'] = 'No drivers selected for deletion.';
    header('Location: delete.php');
    exit;
}

// Parse comma-separated IDs and validate they are integers
$ids = array_map('intval', explode(',', $delete_ids));
$ids = array_filter($ids, function($id) { return $id > 0; });

if (empty($ids)) {
    $_SESSION['error'] = 'Invalid driver IDs.';
    header('Location: delete.php');
    exit;
}

$deletedCount = 0;
$errors = [];

// Delete each driver by ID
foreach ($ids as $id) {
    // Optional: Check if driver has active assignments (hires) before deleting
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM hire WHERE driver_id = ? AND task_assign_status NOT IN ('completed', 'rejected')");
    if ($checkStmt) {
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $checkRow = $checkRes ? $checkRes->fetch_assoc() : null;
        $checkStmt->close();
        
        if ($checkRow && intval($checkRow['cnt']) > 0) {
            $errors[] = "Driver ID $id has active assignments and cannot be deleted.";
            continue;
        }
    }

    // Delete the driver
    $delStmt = $conn->prepare("DELETE FROM drivers WHERE id = ?");
    if ($delStmt) {
        $delStmt->bind_param('i', $id);
        if ($delStmt->execute()) {
            if ($delStmt->affected_rows > 0) {
                $deletedCount++;
            }
        } else {
            $errors[] = "Failed to delete driver ID $id: " . $delStmt->error;
        }
        $delStmt->close();
    } else {
        $errors[] = "Database error: " . $conn->error;
    }
}

// Set session messages
if ($deletedCount > 0) {
    $_SESSION['success'] = "$deletedCount driver(s) deleted successfully.";
}
if (!empty($errors)) {
    $_SESSION['error'] = implode(' | ', $errors);
}

// Redirect back to delete page
header('Location: delete.php');
exit;
?>