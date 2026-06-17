<?php
session_start();

// Verify user is logged in (add your own authentication check)
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: admin_login.php');
//     exit;
// }

$conn = new mysqli('localhost', 'root', '', 'samlina');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch all enquiries
$query = "SELECT id, name, email, subject, message, status, created_at 
          FROM enquire 
          ORDER BY created_at DESC";
$result = $conn->query($query);
$enquiries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Mark enquiry as read (if clicked)
if (isset($_POST['mark_read']) && !empty($_POST['enquiry_id'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $stmt = $conn->prepare("UPDATE enquire SET status = 'read' WHERE id = ?");
    $stmt->bind_param('i', $enquiry_id);
    $stmt->execute();
    $stmt->close();
    header('Location: enquiries.php');
    exit;
}

// Delete enquiry (if clicked)
if (isset($_POST['delete_enquiry']) && !empty($_POST['enquiry_id'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $stmt = $conn->prepare("DELETE FROM enquire WHERE id = ?");
    $stmt->bind_param('i', $enquiry_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['delete_success'] = true;
    header('Location: enquiries.php');
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Enquiries - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #19496c; margin-bottom: 30px; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        th { background: #19496c; color: white; padding: 15px; text-align: left; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #e0e0e0; }
        tr:hover { background: #f9f9f9; }
        .status-unread { background: #ffeaa7; color: #d63031; padding: 4px 10px; border-radius: 4px; font-weight: 600; }
        .status-read { background: #74b9ff; color: #fff; padding: 4px 10px; border-radius: 4px; font-weight: 600; }
        .status-replied { background: #55efc4; color: #00b894; padding: 4px 10px; border-radius: 4px; font-weight: 600; }
        .action-btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; font-size: 12px; }
        .btn-view { background: #19496c; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-view:hover { background: #0f3654; }
        .btn-delete:hover { background: #c0392b; }
        .message-preview { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #666; }
        .empty { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-envelope"></i> Contact Enquiries</h1>
        
        <?php if (isset($_SESSION['delete_success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                ✓ Enquiry deleted successfully
            </div>
            <?php unset($_SESSION['delete_success']); ?>
        <?php endif; ?>

        <?php if (empty($enquiries)): ?>
            <div class="empty">
                <p>No contact enquiries yet.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enquiries as $enquiry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enquiry['name']); ?></td>
                            <td><?php echo htmlspecialchars($enquiry['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($enquiry['subject'], 0, 30)); ?></td>
                            <td class="message-preview"><?php echo htmlspecialchars($enquiry['message']); ?></td>
                            <td>
                                <span class="status-<?php echo $enquiry['status']; ?>">
                                    <?php echo ucfirst($enquiry['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($enquiry['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['id']; ?>">
                                    <?php if ($enquiry['status'] === 'unread'): ?>
                                        <button type="submit" name="mark_read" class="action-btn btn-view" title="Mark as Read">
                                            <i class="fas fa-check"></i> Mark Read
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="delete_enquiry" class="action-btn btn-delete" onclick="return confirm('Delete this enquiry?');" title="Delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>