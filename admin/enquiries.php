<?php
session_start();

// Verify user is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'samlina');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Mark enquiry as read
if (isset($_POST['mark_read']) && !empty($_POST['enquiry_id'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $stmt = $conn->prepare("UPDATE enquire SET status = 'read' WHERE id = ?");
    $stmt->bind_param('i', $enquiry_id);
    if ($stmt->execute()) {
        $_SESSION['enquiry_message'] = 'Marked as read';
    }
    $stmt->close();
    header('Location: enquiries.php');
    exit;
}

// Delete enquiry
if (isset($_POST['delete_enquiry']) && !empty($_POST['enquiry_id'])) {
    $enquiry_id = intval($_POST['enquiry_id']);
    $stmt = $conn->prepare("DELETE FROM enquire WHERE id = ?");
    $stmt->bind_param('i', $enquiry_id);
    if ($stmt->execute()) {
        $_SESSION['enquiry_message'] = 'Enquiry deleted';
    }
    $stmt->close();
    header('Location: enquiries.php');
    exit;
}

// Get filter from URL
$filter = isset($_GET['filter']) ? strtolower($_GET['filter']) : 'all';
if (!in_array($filter, ['all', 'unread', 'read'])) {
    $filter = 'all';
}

// Fetch enquiries
if ($filter === 'all') {
    $query = "SELECT id, name, email, subject, message, status, created_at FROM enquire ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT id, name, email, subject, message, status, created_at FROM enquire WHERE status = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $filter);
}

$stmt->execute();
$result = $stmt->get_result();
$enquiries = [];
while ($row = $result->fetch_assoc()) {
    $enquiries[] = $row;
}
$stmt->close();

// Get counts
$totalResult = $conn->query("SELECT COUNT(*) AS cnt FROM enquire");
$totalRow = $totalResult->fetch_assoc();
$totalCount = (int)($totalRow['cnt'] ?? 0);

$unreadResult = $conn->query("SELECT COUNT(*) AS cnt FROM enquire WHERE status = 'unread'");
$unreadRow = $unreadResult->fetch_assoc();
$unreadCount = (int)($unreadRow['cnt'] ?? 0);

$readResult = $conn->query("SELECT COUNT(*) AS cnt FROM enquire WHERE status = 'read'");
$readRow = $readResult->fetch_assoc();
$readCount = (int)($readRow['cnt'] ?? 0);

$conn->close();

$message = $_SESSION['enquiry_message'] ?? '';
unset($_SESSION['enquiry_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Enquiries - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { color: #19496c; font-size: 1.8rem; }
        .back-btn { background: #19496c; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .back-btn:hover { background: #0f3654; }
        
        .stats { display: flex; gap: 16px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-box { background: #fff; padding: 20px; border-radius: 8px; flex: 1; min-width: 150px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat-box strong { display: block; font-size: 1.8rem; color: #19496c; margin-bottom: 8px; }
        .stat-box p { color: #666; font-size: 0.9rem; }
        
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .filter-btn { padding: 10px 16px; border: 1px solid #e0e0e0; background: #fff; border-radius: 6px; cursor: pointer; text-decoration: none; color: #333; font-weight: 500; }
        .filter-btn.active { background: #be985b; color: #fff; border-color: #be985b; }
        .filter-btn:hover { background: #f5f5f5; }
        .filter-btn.active:hover { background: #a67e47; }
        
        .enquiries-list { display: flex; flex-direction: column; gap: 16px; }
        .enquiry-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #be985b; }
        .enquiry-card.unread { border-left-color: #ffeaa7; background: #fffbf0; }
        
        .enquiry-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; }
        .enquiry-title { margin: 0; color: #19496c; font-size: 1.1rem; }
        .enquiry-from { color: #666; font-size: 0.9rem; margin-top: 4px; }
        
        .status-badge { display: inline-block; padding: 6px 10px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .status-unread { background: #ffeaa7; color: #d63031; }
        .status-read { background: #d4edda; color: #155724; }
        
        .enquiry-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin: 12px 0; }
        .meta-item { font-size: 0.9rem; }
        .meta-item strong { color: #19496c; }
        
        .enquiry-message { background: #f9f9f9; padding: 12px; border-radius: 6px; margin: 12px 0; color: #555; line-height: 1.5; }
        .enquiry-message p { margin: 0; }
        
        .enquiry-actions { display: flex; gap: 8px; margin-top: 12px; }
        .action-btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; }
        .btn-read { background: #004085; color: #fff; }
        .btn-read:hover { background: #003366; }
        .btn-delete { background: #b71c1c; color: #fff; }
        .btn-delete:hover { background: #8b0000; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }
        
        .flash-message { position: fixed; top: 20px; right: 20px; background: #0b7a47; color: #fff; padding: 14px 20px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 2000; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #19496c 0%, #2a5a8d 100%);
            padding: 20px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .dashboard-header h1 { margin: 0; color: #fff; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1><i class="fas fa-envelope"></i> Contact Enquiries</h1>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="flash-message" id="flashMsg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-box">
                <strong><?php echo $totalCount; ?></strong>
                <p>Total Enquiries</p>
            </div>
            <div class="stat-box">
                <strong style="color: #d63031;"><?php echo $unreadCount; ?></strong>
                <p>Unread</p>
            </div>
            <div class="stat-box">
                <strong style="color: #0b7a47;"><?php echo $readCount; ?></strong>
                <p>Read</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="enquiries.php" class="filter-btn <?php echo ($filter === 'all') ? 'active' : ''; ?>">All (<?php echo $totalCount; ?>)</a>
            <a href="enquiries.php?filter=unread" class="filter-btn <?php echo ($filter === 'unread') ? 'active' : ''; ?>">Unread (<?php echo $unreadCount; ?>)</a>
            <a href="enquiries.php?filter=read" class="filter-btn <?php echo ($filter === 'read') ? 'active' : ''; ?>">Read (<?php echo $readCount; ?>)</a>
        </div>

        <!-- Enquiries List -->
        <div class="enquiries-list">
            <?php if (empty($enquiries)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No enquiries</h3>
                    <p>There are no contact enquiries to display.</p>
                </div>
            <?php else: ?>
                <?php foreach ($enquiries as $e): 
                    $isUnread = ($e['status'] === 'unread');
                    $cardClass = $isUnread ? 'unread' : '';
                ?>
                    <div class="enquiry-card <?php echo $cardClass; ?>">
                        <div class="enquiry-header">
                            <div style="flex: 1;">
                                <h3 class="enquiry-title"><?php echo htmlspecialchars($e['subject']); ?></h3>
                                <div class="enquiry-from">From: <strong><?php echo htmlspecialchars($e['name']); ?></strong></div>
                            </div>
                            <span class="status-badge status-<?php echo $e['status']; ?>">
                                <?php echo ucfirst($e['status']); ?>
                            </span>
                        </div>

                        <div class="enquiry-meta">
                            <div class="meta-item">
                                <strong>Email:</strong><br><?php echo htmlspecialchars($e['email']); ?>
                            </div>
                            <div class="meta-item">
                                <strong>Date:</strong><br><?php echo date('M d, Y H:i', strtotime($e['created_at'])); ?>
                            </div>
                        </div>

                        <div class="enquiry-message">
                            <p><?php echo nl2br(htmlspecialchars($e['message'])); ?></p>
                        </div>

                        <div class="enquiry-actions">
                            <?php if ($e['status'] === 'unread'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="enquiry_id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" name="mark_read" class="action-btn btn-read">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="enquiry_id" value="<?php echo $e['id']; ?>">
                                <button type="submit" name="delete_enquiry" class="action-btn btn-delete" onclick="return confirm('Delete this enquiry?');">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide flash message after 4 seconds
        (function() {
            const flash = document.getElementById('flashMsg');
            if (!flash) return;
            setTimeout(() => {
                flash.style.transition = 'opacity 0.4s';
                flash.style.opacity = '0';
                setTimeout(() => flash.remove(), 400);
            }, 4000);
        })();
    </script>
</body>
</html>