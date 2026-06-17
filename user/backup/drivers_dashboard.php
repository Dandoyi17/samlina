

<?php
require_once '../../db/config.php';

// Get driver from session
$driver_id = $_SESSION['driver_id'] ?? null;

if (!$driver_id) {
    header('Location: drivers_login.php');
    exit;
}

// Fetch driver info
$driverStmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
$driverStmt->bind_param("s", $driver_id);
$driverStmt->execute();
$driverResult = $driverStmt->get_result();
$driverInfo = $driverResult->fetch_assoc();
$driverStmt->close();

// Fetch all assigned tasks for this driver
$assignedTasks = [];
$tasksStmt = $conn->prepare("SELECT * FROM hire WHERE driver_id = ? ORDER BY created_at DESC");
$tasksStmt->bind_param("s", $driver_id);
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

while ($row = $tasksResult->fetch_assoc()) {
    $assignedTasks[] = $row;
}
$tasksStmt->close();

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

<!-- Display assigned tasks table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Assigned Tasks</h2>
        <p class="muted small">Total: <?= count($assignedTasks) ?></p>
    </div>

    <div class="table-wrap">
        <table class="responsive-table">
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
                    <?php foreach ($assignedTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['booking_id']) ?></td>
                            <td><?= htmlspecialchars($task['fullName']) ?></td>
                            <td><?= htmlspecialchars($task['pickupLocation']) ?></td>
                            <td><?= htmlspecialchars($task['destination']) ?></td>
                            <td><?= htmlspecialchars($task['vehicleType']) ?></td>
                            <td><?= htmlspecialchars($task['pickupDate']) ?></td>
                            <td>
                                <?php
                                    $assignStatus = strtolower($task['task_assign_status'] ?? 'assigned');
                                    $statusClass = 'pill ' . str_replace(' ', '_', $assignStatus);
                                ?>
                                <span class="<?= $statusClass ?>"><?= ucfirst($assignStatus) ?></span>
                            </td>
                            <td class="actions-col">
                                <a href="task_details.php?booking_id=<?= urlencode($task['booking_id']) ?>" class="action-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Task Statistics -->
<div style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap;">
    <div class="card" style="flex:1;min-width:200px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;color:#1976d2;"><?= $statusCounts['assigned'] ?></div>
        <div class="muted">Assigned</div>
    </div>
    <div class="card" style="flex:1;min-width:200px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;color:#f57c00;"><?= $statusCounts['ongoing'] ?></div>
        <div class="muted">Ongoing</div>
    </div>
    <div class="card" style="flex:1;min-width:200px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;color:#0b7a47;"><?= $statusCounts['completed'] ?></div>
        <div class="muted">Completed</div>
    </div>
    <div class="card" style="flex:1;min-width:200px;text-align:center;">
        <div style="font-size:2rem;font-weight:700;color:#b71c1c;"><?= $statusCounts['rejected'] ?></div>
        <div class="muted">Rejected</div>
    </div>
</div>