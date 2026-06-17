<?php
// drivers_dashboard.php
session_start();
require_once '../../db/config.php';

// If not logged in, redirect to login
$driver_id = $_SESSION['driver_id'] ?? null;
if (!$driver_id) {
    header('Location: drivers_login.php');
    exit;
}

// Handle inline POST to update task assignment status (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = trim($_POST['booking_id'] ?? '');
    $new_status = trim($_POST['new_status'] ?? '');

    if ($booking_id !== '' && $new_status !== '') {
        // Ensure this booking belongs to the logged-in driver
        $check = $conn->prepare("SELECT id FROM hire WHERE booking_id = ? AND driver_id = ? LIMIT 1");
        if ($check) {
            $check->bind_param('si', $booking_id, $driver_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $update = $conn->prepare("UPDATE hire SET task_assign_status = ? WHERE booking_id = ? AND driver_id = ?");
                if ($update) {
                    $update->bind_param('ssi', $new_status, $booking_id, $driver_id);
                    $update->execute();
                    $update->close();
                }
            }
            $check->close();
        }
    }

    // Redirect to avoid form resubmission (PRG)
    header('Location: drivers_dashboard.php');
    exit;
}

// Fetch driver info
$driverStmt = $conn->prepare("SELECT id, driver_id, name, username, email, phone, vehicle, status FROM drivers WHERE id = ?");
if ($driverStmt) {
    $driverStmt->bind_param("i", $driver_id);
    $driverStmt->execute();
    $driverResult = $driverStmt->get_result();
    $driverInfo = $driverResult ? $driverResult->fetch_assoc() : null;
    $driverStmt->close();
} else {
    $driverInfo = null;
}

// Fetch assigned tasks for this driver
$assignedTasks = [];
$tasksStmt = $conn->prepare("SELECT * FROM hire WHERE driver_id = ? ORDER BY created_at DESC");
if ($tasksStmt) {
    $tasksStmt->bind_param("i", $driver_id);
    $tasksStmt->execute();
    $tasksResult = $tasksStmt->get_result();
    if ($tasksResult) {
        while ($row = $tasksResult->fetch_assoc()) {
            $assignedTasks[] = $row;
        }
        $tasksResult->free();
    }
    $tasksStmt->close();
}

// Count tasks by status
$statusCounts = [
    'assigned' => 0,
    'processing' => 0,
    'ongoing' => 0,
    'completed' => 0,
    'rejected' => 0
];

foreach ($assignedTasks as $task) {
    $status = strtolower($task['task_assign_status'] ?? 'assigned');
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Driver Dashboard — Samlina Global</title>
    <link rel="stylesheet" href="../css/drivers_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Small inline adjustments to ensure pills and actions look tidy */
        .pill { padding:.25rem .5rem; border-radius:999px; display:inline-block; font-size:.9rem; color:#fff; }
        .pill.assigned { background:#1976d2; }
        .pill.processing { background:#f57c00; }
        .pill.ongoing { background:#00796b; }
        .pill.completed { background:#2e7d32; }
        .pill.rejected { background:#b71c1c; }
        .actions-col { white-space:nowrap; }
        .small-form { display:inline-block; margin:0; }
        .small-select { padding:.25rem .4rem; font-size:.9rem; }
        .small-btn { padding:.35rem .5rem; font-size:.85rem; margin-left:.3rem; }
    </style>
</head>

<body class="driver-dashboard-page">
    <header class="topbar">
        <div class="topbar-left">
            <img class="brand-logo" src="../../logo/logowhite.png" alt="Samlina Global logo">
            <div class="brand-text">
                <h1>Samlina Global</h1>
                <p class="tag">Driver Dashboard</p>
            </div>
        </div>

        <button id="hamburger" class="hamburger" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <nav id="topNav" class="topnav" aria-hidden="true">
            <a href="../../index.php">Home</a>
            <a href="drivers_dashboard.php" class="active">Dashboard</a>
            <a href="drivers_profile.php">Profile</a>
            <a href="logout.php" id="logoutLink">Logout</a>
        </nav>
    </header>

    <main class="dashboard-main">
        <section class="welcome-card">
            <h2>Welcome, <?= htmlspecialchars($driverInfo['name'] ?? $driverInfo['username'] ?? 'Driver') ?></h2>
            <p class="muted">Here are your current assignments and quick stats.</p>
        </section>

        <!-- Task Statistics -->
        <section style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap;">
            <div class="card" style="flex:1;min-width:200px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#1976d2;"><?= $statusCounts['assigned'] ?></div>
                <div class="muted">Assigned</div>
            </div>
            <div class="card" style="flex:1;min-width:200px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#f57c00;"><?= $statusCounts['processing'] ?></div>
                <div class="muted">Processing</div>
            </div>
            <div class="card" style="flex:1;min-width:200px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#0b7a47;"><?= $statusCounts['ongoing'] ?></div>
                <div class="muted">Ongoing</div>
            </div>
            <div class="card" style="flex:1;min-width:200px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#2e7d32;"><?= $statusCounts['completed'] ?></div>
                <div class="muted">Completed</div>
            </div>
            <div class="card" style="flex:1;min-width:200px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#b71c1c;"><?= $statusCounts['rejected'] ?></div>
                <div class="muted">Rejected</div>
            </div>
        </section>

        <!-- Display assigned tasks table -->
        <section class="tasks-section" style="margin-top:1rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Assigned Tasks</h2>
                    <p class="muted small">Total: <?= count($assignedTasks) ?></p>
                </div>

                <div class="table-wrap">
                    <table class="responsive-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Client</th>
                                <th>Pickup Location</th>
                                <th>Destination</th>
                                <th>Vehicle Type</th>
                                <th>Pickup Date</th>
                                <th>Assignment Status</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignedTasks)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:1rem;color:#666;">No assigned tasks yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignedTasks as $task): 
                                    $assignStatus = strtolower($task['task_assign_status'] ?? 'assigned');
                                    $statusClass = 'pill ' . str_replace(' ', '_', $assignStatus);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($task['booking_id']) ?></td>
                                        <td><?= htmlspecialchars($task['fullName']) ?></td>
                                        <td><?= htmlspecialchars($task['pickupLocation']) ?></td>
                                        <td><?= htmlspecialchars($task['destination']) ?></td>
                                        <td><?= htmlspecialchars($task['vehicleType']) ?></td>
                                        <td><?= htmlspecialchars($task['pickupDate']) ?></td>
                                        <td>
                                            <span class="<?= $statusClass ?>"><?= ucfirst($assignStatus) ?></span>
                                        </td>
                                        <td class="actions-col">
                                            <a href="task_details.php?booking_id=<?= urlencode($task['booking_id']) ?>" class="action-btn" style="margin-right:.5rem;">
                                                <i class="fas fa-eye"></i> View
                                            </a>

                                            
                                            <!-- Inline small form to change assignment status -->
                                            <form class="small-form" method="post" action="drivers_dashboard.php" style="display:inline-block;">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($task['booking_id']) ?>">
                                                <select name="new_status" class="small-select" aria-label="Change status">
                                                    <option value="assigned" <?= $assignStatus === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                                    <option value="processing" <?= $assignStatus === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="ongoing" <?= $assignStatus === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                                    <option value="completed" <?= $assignStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="rejected" <?= $assignStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                </select>
                                            
                                                <button type="submit" name="update_status" value="1" class="small-btn btn">Update</button>
                                            
                                                <?php if ($assignStatus === 'completed'): ?>
                                                    <!-- Show share button when status is completed -->
                                                    <button type="button" class="small-btn btn" style="margin-left:.4rem;background:#1976d2;color:#fff;" onclick="openShareModal('<?= htmlspecialchars($task['booking_id']) ?>')">
                                                        <i class="fas fa-share-alt"></i> Share Rating
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <footer class="dashboard-footer">
        <p>&copy; <span id="year"></span> Samlina Global Nig Limited</p>
    </footer>

    <script>
    // small toggle for mobile nav (existing behavior)
    document.getElementById('hamburger')?.addEventListener('click', function(){
        var nav = document.getElementById('topNav');
        if (!nav) return;
        var hidden = nav.getAttribute('aria-hidden') === 'true';
        nav.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    });

    // set current year in footer
    document.getElementById('year').textContent = new Date().getFullYear();
    </script>

        <!-- Share Rating Modal (single shared modal used for all bookings) -->
    <style>
    /* Minimal styles for the share modal */
    #shareModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:2000; }
    #shareModal.open { display:flex; }
    .share-panel { background:#fff; padding:1rem; border-radius:8px; width:95%; max-width:520px; box-shadow:0 12px 40px rgba(0,0,0,.25); }
    .share-panel h3 { margin:0 0 .5rem 0; }
    .share-row { display:flex; gap:.5rem; align-items:center; margin-top:.6rem; }
    .share-input { flex:1; padding:.5rem; border:1px solid #e3e6ea; border-radius:6px; font-size:.95rem; }
    .share-actions { display:flex; gap:.5rem; margin-top:.8rem; justify-content:flex-end; }
    .btn-ghost { background:#f3f3f3;border:0;padding:.5rem .7rem;border-radius:6px;cursor:pointer; }
    .btn-primary { background:#1976d2;color:#fff;border:0;padding:.5rem .8rem;border-radius:6px;cursor:pointer; }
    .small-muted { font-size:.9rem;color:#666;margin-top:.4rem; }
    </style>
    
    <div id="shareModal" role="dialog" aria-hidden="true" aria-labelledby="shareTitle">
      <div class="share-panel" role="document">
        <button id="shareClose" aria-label="Close" style="float:right;border:0;background:transparent;font-size:1.2rem;cursor:pointer;">&times;</button>
        <h3 id="shareTitle">Share Rating Link</h3>
        <p class="small-muted">Copy or open the link below and send it to the client so they can rate this completed task.</p>
    
        <div class="share-row">
          <input id="shareLinkInput" class="share-input" readonly aria-readonly="true" value="">
          <button id="copyShareBtn" class="btn-ghost" type="button">Copy</button>
        </div>
    
        <div class="share-actions">
          <button id="openShareBtn" class="btn-primary" type="button">Open Link</button>
          <button id="closeShareBtn" class="btn-ghost" type="button">Close</button>
        </div>
      </div>
    </div>
    
    <script>
    (function(){
    // Adjust this path if you placed rate_driver.php somewhere else.
    // It will be combined with window.location.origin to build an absolute link.
    // The rating page for this project is located at `/samlina/user/rate_driver.php`.
    // If you move `rate_driver.php`, update this path accordingly.
    const ratingPath = '/samlina/user/rate_driver.php?booking_id=';
    
      const modal = document.getElementById('shareModal');
      const linkInput = document.getElementById('shareLinkInput');
      const copyBtn = document.getElementById('copyShareBtn');
      const openBtn = document.getElementById('openShareBtn');
      const closeBtns = [document.getElementById('closeShareBtn'), document.getElementById('shareClose')];
    
      function openShareModal(bookingId) {
        if (!bookingId) return;
        const origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
        const url = origin + ratingPath + encodeURIComponent(bookingId);
        linkInput.value = url;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        linkInput.select();
      }
    
      function closeShareModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        linkInput.value = '';
      }
    
      function copyToClipboard() {
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(linkInput.value).then(function(){ 
              copyBtn.textContent = 'Copied';
              setTimeout(()=> copyBtn.textContent = 'Copy', 1800);
            }, function(){ fallbackCopy(); });
          } else {
            fallbackCopy();
          }
        } catch (e) { fallbackCopy(); }
      }
    
      function fallbackCopy(){
        linkInput.select();
        try {
          document.execCommand('copy');
          copyBtn.textContent = 'Copied';
          setTimeout(()=> copyBtn.textContent = 'Copy', 1800);
        } catch (e) {
          alert('Copy failed — please select the link and copy manually.');
        }
      }
    
      // Open in new tab
      function openLink() {
        const u = linkInput.value;
        if (u) window.open(u, '_blank');
      }
    
      // expose function globally to be callable from inline onclick
      window.openShareModal = openShareModal;
    
      copyBtn.addEventListener('click', copyToClipboard);
      openBtn.addEventListener('click', openLink);
      closeBtns.forEach(b => { if (b) b.addEventListener('click', closeShareModal); });
    
      // close on backdrop click and ESC
      modal.addEventListener('click', function(e){
        if (e.target === modal) closeShareModal();
      });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeShareModal(); });
    })();
    </script>
</body>

</html>