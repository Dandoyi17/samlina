<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Process approve/reject actions (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'];
    $bookingId = $_POST['booking_id'];

    $conn = new mysqli('localhost', 'root', '', 'samlina');
    if (!$conn->connect_error) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE hire SET status = 'approved' WHERE booking_id = ?");
            $stmt->bind_param('s', $bookingId);
            $ok = $stmt->execute();
            $stmt->close();
            $_SESSION['dashboard_message'] = $ok ? "Booking $bookingId approved." : "Failed to approve $bookingId.";
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            // Append admin reason to notes (preserves previous notes)
            $prefix = "\n\nAdmin reason: " . $reason;
            $stmt = $conn->prepare("UPDATE hire SET status = 'rejected', notes = CONCAT(IFNULL(notes,''), ?) WHERE booking_id = ?");
            $stmt->bind_param('ss', $prefix, $bookingId);
            $ok = $stmt->execute();
            $stmt->close();
            $_SESSION['dashboard_message'] = $ok ? "Booking $bookingId rejected." : "Failed to reject $bookingId.";
        }
        $conn->close();
    } else {
        $_SESSION['dashboard_message'] = 'Database connection failed.';
    }

    // Redirect back to GET with same status filter if present
    $statusParam = isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . $statusParam);
    exit;
}

// Helper: get counts by status (case-insensitive)
function getCount($conn, $status = null) {
    if ($status === null) {
        $sql = "SELECT COUNT(*) AS cnt FROM hire";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT COUNT(*) AS cnt FROM hire WHERE LOWER(status) = ?";
        $stmt = $conn->prepare($sql);
        $s = strtolower($status);
        $stmt->bind_param('s', $s);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return (int)($row['cnt'] ?? 0);
}

// Connect to get counts and bookings
$conn = new mysqli('localhost', 'root', '', 'samlina');
if ($conn->connect_error) {
    die('Database connection failed.');
}

$pendingCount = getCount($conn, 'pending');
$approvedCount = getCount($conn, 'approved');
$rejectedCount = getCount($conn, 'rejected');
$totalCount = getCount($conn);

// Which status to show
$status = isset($_GET['status']) ? strtolower($_GET['status']) : 'all';
$valid = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status, $valid)) $status = 'all';

// Fetch bookings for display
if ($status === 'all') {
    $sql = "SELECT * FROM hire ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM hire WHERE LOWER(status) = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $s = strtolower($status);
    $stmt->bind_param('s', $s);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
$conn->close();

// Optional flash message
$flash = $_SESSION['dashboard_message'] ?? '';
unset($_SESSION['dashboard_message']);

// Initialize enquiry variables
$totalEnquiries = 0;
$unreadEnquiries = 0;
$readEnquiries = 0;

// Reconnect for enquiry counts
$conn2 = new mysqli('localhost', 'root', '', 'samlina');
if (!$conn2->connect_error) {
    // Total enquiries
    $result = $conn2->query("SELECT COUNT(*) AS cnt FROM enquire");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalEnquiries = (int)($row['cnt'] ?? 0);
    }

    // Unread enquiries
    $result = $conn2->query("SELECT COUNT(*) AS cnt FROM enquire WHERE status = 'unread'");
    if ($result) {
        $row = $result->fetch_assoc();
        $unreadEnquiries = (int)($row['cnt'] ?? 0);
    }

    // Read enquiries
    $result = $conn2->query("SELECT COUNT(*) AS cnt FROM enquire WHERE status = 'read'");
    if ($result) {
        $row = $result->fetch_assoc();
        $readEnquiries = (int)($row['cnt'] ?? 0);
    }

    $conn2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - Samlina Global</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* small local adjustments */
        .dashboard-stats { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-card { background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);flex:1;min-width:160px;cursor:pointer;text-align:center; }
        .stat-number { font-size:1.6rem; font-weight:700; margin-top:8px; color:#19496c; }
        .filter-buttons { margin:16px 0; display:flex; gap:8px; flex-wrap:wrap; }
        .filter-btn { padding:8px 14px; border-radius:6px; border:1px solid #e0e0e0; background:#fff; cursor:pointer; }
        .filter-btn.active { background:#be985b;color:#fff; border-color:#be985b; }
        .booking-card { background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.04); margin-bottom:12px; }
        .booking-status { padding:6px 10px;border-radius:6px;font-weight:700;text-transform:capitalize; }
        .status-approved { background:#d4edda;color:#155724; }
        .status-pending { background:#cce5ff;color:#004085; }
        .status-rejected { background:#f8d7da;color:#721c24; }
        .booking-actions { margin-top:12px; display:flex; gap:8px; }
        .approve-btn { background:#0b7a47;color:#fff;padding:8px 12px;border-radius:6px;border:0;cursor:pointer; }
        .reject-btn { background:#b71c1c;color:#fff;padding:8px 12px;border-radius:6px;border:0;cursor:pointer; }
        .empty-state { text-align:center;padding:40px 0;color:#666; }
        .flash { position: fixed; top: 18px; right: 18px; background:#0b7a47; color:#fff; padding:12px 18px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.12); z-index:2000; }
    </style>
</head>
<body class="dashboard">
    <header class="dashboard-header">
        <div class="dashboard-nav">
            <div style="display:flex; gap:12px; align-items:center;">
                <img src="../logo/logowhite.png" alt="Samlina" style="height:48px;">
                <div>
                    <h1 style="margin:0;font-size:1.1rem;">Admin Dashboard</h1>
                    <small style="opacity:0.85;">Samlina Global Nig Limited</small>
                </div>
            </div>

            <div style="margin-left:auto;display:flex;align-items:center;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-user-circle"></i>
                    <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>
                </div>
                <a href="fleet.php" class="logout-btn">Fleets</a>
                <a href="admin_drivers.php" class="logout-btn">Drivers</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" style="padding:20px;">
        <?php if ($flash): ?>
            <div class="flash" id="flashMessage"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <!-- Statistics (clickable to filter) -->
        <div class="dashboard-stats" role="navigation" aria-label="Booking status filters">
            <a href="?status=pending" style="text-decoration:none;color:inherit;">
                <div class="stat-card" title="Show pending bookings">
                    <i class="fas fa-hourglass-half" style="font-size:2rem;color:#be985b;"></i>
                    <div class="stat-number" id="pendingCount"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </a>

            <a href="?status=approved" style="text-decoration:none;color:inherit;">
                <div class="stat-card" title="Show approved bookings">
                    <i class="fas fa-check-circle" style="font-size:2rem;color:#0b7a47;"></i>
                    <div class="stat-number" id="approvedCount"><?php echo $approvedCount; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </a>

            <a href="?status=rejected" style="text-decoration:none;color:inherit;">
                <div class="stat-card" title="Show rejected bookings">
                    <i class="fas fa-times-circle" style="font-size:2rem;color:#b71c1c;"></i>
                    <div class="stat-number" id="rejectedCount"><?php echo $rejectedCount; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </a>

            <a href="?status=all" style="text-decoration:none;color:inherit;">
                <div class="stat-card" title="Show all bookings">
                    <i class="fas fa-car" style="font-size:2rem;color:#19496c;"></i>
                    <div class="stat-number" id="totalCount"><?php echo $totalCount; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </a>

            <a href="enquiries.php" target="_blank" style="text-decoration:none;color:inherit;">
    <div class="stat-card" title="View all contact enquiries">
        <i class="fas fa-envelope" style="font-size:2rem;color:#be985b;"></i>
        <div style="margin-top:8px;">
            <div class="stat-number" style="color:#19496c;"><?php echo $totalEnquiries; ?></div>
            <div style="font-size:0.85rem;color:#666;margin-top:4px;">
                <span style="background:#ffeaa7;color:#d63031;padding:2px 6px;border-radius:3px;margin-right:4px;font-weight:600;">
                    <?php echo $unreadEnquiries; ?> Unread
                </span>
                <span style="background:#e3f2fd;color:#004085;padding:2px 6px;border-radius:3px;font-weight:600;">
                    <?php echo $readEnquiries; ?> Read
                </span>
            </div>
        </div>
        <!-- <div class="stat-label" style="margin-top:8px;">Total Enquiries</div> -->
    </div>
</a>
        </div>

        <!-- Filter Buttons -->
        <div class="section-header" style="margin-top:18px;">
            <h2>Booking Requests</h2>
        </div>
        <div class="filter-buttons" role="tablist" aria-label="Booking filters">
            <?php
            $btns = [
                'all' => 'All Bookings',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected'
            ];
            foreach ($btns as $key => $label) {
                $active = ($status === $key) ? 'active' : '';
                $href = ($key === 'all') ? 'dashboard.php' : 'dashboard.php?status=' . $key;
                echo "<a href=\"$href\" class=\"filter-btn $active\">$label</a>";
            }
            ?>
        </div>

        <!-- Bookings List -->
        <div id="bookingsList" style="margin-top:12px;">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:8px;"></i>
                    <h3>No bookings</h3>
                    <p>There are no booking requests for this filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $b): 
                    $statusClass = 'status-pending';
                    $s = strtolower($b['status'] ?? 'pending');
                    if ($s === 'approved') $statusClass = 'status-approved';
                    elseif ($s === 'rejected') $statusClass = 'status-rejected';
                    $bid = htmlspecialchars($b['booking_id']);
                ?>
                    <div class="booking-card" aria-labelledby="booking-<?php echo $bid; ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <h3 id="booking-<?php echo $bid; ?>" style="margin:0;color:#19496c;"><?php echo htmlspecialchars($b['fullName']); ?></h3>
                                <div style="color:#666;font-size:0.9rem;">Booking ID: <strong><?php echo $bid; ?></strong></div>
                            </div>
                            <div>
                                <span class="booking-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($s)); ?></span>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px;">
                            <div>
                                <strong>Email</strong>
                                <div><?php echo htmlspecialchars($b['email']); ?></div>
                            </div>
                            <div>
                                <strong>Phone</strong>
                                <div><?php echo htmlspecialchars($b['phone']); ?></div>
                            </div>
                            <div>
                                <strong>Vehicle</strong>
                                <div><?php echo htmlspecialchars($b['vehicleType']); ?></div>
                            </div>
                            <div>
                                <strong>Pickup</strong>
                                <div><?php echo htmlspecialchars($b['pickupDate']); ?> <?php echo htmlspecialchars($b['pickupTime']); ?></div>
                            </div>
                            <div>
                                <strong>Return</strong>
                                <div><?php echo htmlspecialchars($b['returnDate']); ?> <?php echo htmlspecialchars($b['returnTime']); ?></div>
                            </div>
                            <div>
                                <strong>Route</strong>
                                <div><?php echo htmlspecialchars($b['pickupLocation']); ?> → <?php echo htmlspecialchars($b['destination']); ?></div>
                            </div>
                        </div>

                        <?php if (!empty($b['notes'])): ?>
                            <div style="margin-top:12px;color:#555;"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($b['notes'])); ?></div>
                        <?php endif; ?>

                        <?php if ($s === 'pending'): ?>
                            <div class="booking-actions">
                                <!-- Approve form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $bid; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="approve-btn"> <i class="fas fa-check"></i> Approve</button>
                                </form>

                                <!-- Reject form (reason filled by prompt) -->
                                <form method="POST" id="reject-form-<?php echo $bid; ?>" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $bid; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="reason" id="reject-reason-<?php echo $bid; ?>" value="">
                                    <button type="button" class="reject-btn" onclick="promptReject('<?php echo $bid; ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Prompt for rejection reason, set hidden input and submit the form
        function promptReject(bookingId) {
            const reason = prompt('Please enter a reason for rejecting this booking (optional):');
            if (reason === null) return; // user cancelled
            const input = document.getElementById('reject-reason-' + bookingId);
            if (input) input.value = reason;
            const form = document.getElementById('reject-form-' + bookingId);
            if (form) form.submit();
        }

        // Auto-hide flash
        (function() {
            const f = document.getElementById('flashMessage');
            if (!f) return;
            setTimeout(() => {
                f.style.transition = 'opacity 0.4s';
                f.style.opacity = '0';
                setTimeout(() => f.remove(), 450);
            }, 3000);
        })();
    </script>
</body>
</html>