<?php
require_once '../db/config.php';

// Handle driver assignment (POST from modal form)
$assignMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id']) && isset($_POST['driver_id'])) {
    $task_id = $_POST['task_id'];
    $driver_id = $_POST['driver_id'];
    $instructions = $_POST['instructions'] ?? '';

    // Start transaction for data integrity
    $conn->begin_transaction();

    try {
        // 1. Update hire table with driver assignment
        $stmt1 = $conn->prepare("UPDATE hire SET driver_id=?, instructions=?, task_assign_status='assigned' WHERE booking_id=?");
        $stmt1->bind_param("sss", $driver_id, $instructions, $task_id);
        $stmt1->execute();

        // 2. Insert into assignment_history table
        $stmt2 = $conn->prepare("INSERT INTO assignment_history (booking_id, driver_id, instructions, task_assign_status) VALUES (?, ?, ?, 'assigned')");
        $stmt2->bind_param("sss", $task_id, $driver_id, $instructions);
        $stmt2->execute();

        // 3. Update driver's assigned_tasks count
        $stmt3 = $conn->prepare("UPDATE drivers SET assigned_tasks = assigned_tasks + 1 WHERE id=?");
        $stmt3->bind_param("s", $driver_id);
        $stmt3->execute();

        // Commit transaction
        $conn->commit();
        $assignMessage = 'Driver assigned successfully!';

        // Refresh tasks list
        $tasks = [];
        $result = $conn->query("SELECT * FROM hire WHERE status='approved' ORDER BY created_at DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }

        $stmt1->close();
        $stmt2->close();
        $stmt3->close();

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $assignMessage = 'Error assigning driver: ' . $e->getMessage();
    }
}

// Fetch approved tasks from hire table
$tasks = [];
$result = $conn->query("SELECT * FROM hire WHERE status='approved' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

// Fetch all drivers for assignment dropdown
$drivers = [];
$driverResult = $conn->query("SELECT id, name, phone, status, assigned_tasks FROM drivers ORDER BY name");
if ($driverResult) {
    while ($row = $driverResult->fetch_assoc()) {
        $drivers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Assign Tasks - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/assign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pill { font-size: 0.85rem; padding: 0.25rem 0.5rem; border-radius: 12px; color: #fff; font-weight: 700; display: inline-block; }
        .pill.approved { background: #0b7a47; }
        .pill.assigned { background: #be985b; }
        .pill.processing { background: #1976d2; }
        .pill.ongoing { background: #f57c00; }
        .pill.completed { background: #0b7a47; }
        .pill.rejected { background: #b71c1c; }
        .action-btn { display: inline-flex; gap: 0.45rem; align-items: center; padding: 0.35rem 0.6rem; border-radius: 6px; background: #19496c; color: #fff; text-decoration: none; font-weight: 600; border: 0; cursor: pointer; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1200; align-items: center; justify-content: center; }
        .modal.open { display: flex; }
        .modal-backdrop { position: absolute; inset: 0; }
        .modal-panel { position: relative; background: #fff; border-radius: 10px; padding: 1.25rem; width: 95%; max-width: 600px; max-height: 90vh; overflow: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.25); z-index: 1201; }
        .modal-close { position: absolute; right: 0.6rem; top: 0.4rem; background: transparent; border: 0; font-size: 1.6rem; cursor: pointer; }
        .page-container { padding: 1.25rem; max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 8px; padding: 0.75rem; box-shadow: 0 6px 20px rgba(0,0,0,0.04); }
        .card-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #f1f2f3; }
        .card-title { margin: 0; font-size: 1.05rem; }
        .muted { color: #6c757d; }
        .table-wrap { overflow: auto; max-height: 60vh; padding-top: 0.5rem; }
        .responsive-table { width: 100%; border-collapse: collapse; }
        .responsive-table thead th { text-align: left; padding: 0.8rem; background: #fbfbfb; font-size: 0.92rem; border-bottom: 1px solid #eee; }
        .responsive-table tbody td { padding: 0.7rem 0.8rem; border-bottom: 1px solid #f2f3f4; vertical-align: middle; font-size: 0.95rem; }
        .detail-item { padding: 0.5rem 0; }
        .detail-label { font-weight: 600; font-size: 0.9rem; }
        .detail-value { margin-top: 0.3rem; }
        input[type="text"], input[type="email"], input[type="tel"], select, textarea { width: 100%; padding: 0.5rem; margin-top: 0.3rem; border: 1px solid #e3e6ea; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; }
        .btn { padding: 0.6rem 0.9rem; border-radius: 6px; font-weight: 700; cursor: pointer; border: 0; }
        .btn-primary { background: #19496c; color: #fff; }
        .message { margin-top: 1rem; padding: 0.75rem; border-radius: 6px; }
        .message-success { background: #e8f5e9; color: #0b7a47; }
        .message-error { background: #ffebee; color: #b71c1c; }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Assign Tasks</h1>
            </div>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Approved Tasks Awaiting Assignment</h2>
                <p class="muted small">Showing <span id="assignTotal"><?= count($tasks) ?></span> tasks</p>
            </div>

            <div class="table-wrap">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Vehicle Type</th>
                            <th>Pickup Location</th>
                            <th>Destination</th>
                            <th>Assigned Driver</th>
                            <th>Assignment Status</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;padding:1rem;color:#666;">No approved tasks awaiting assignment</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['booking_id']) ?></td>
                                    <td><?= htmlspecialchars($task['fullName']) ?></td>
                                    <td><?= htmlspecialchars($task['phone']) ?></td>
                                    <td><?= htmlspecialchars($task['vehicleType']) ?></td>
                                    <td><?= htmlspecialchars($task['pickupLocation']) ?></td>
                                    <td><?= htmlspecialchars($task['destination']) ?></td>
                                    <td>
                                        <?php
                                            $driverDisplay = 'Not Assigned';
                                            if (!empty($task['driver_id'])) {
                                                $driverStmt = $conn->prepare("SELECT name FROM drivers WHERE id = ?");
                                                $driverStmt->bind_param("s", $task['driver_id']);
                                                $driverStmt->execute();
                                                $driverResult = $driverStmt->get_result();
                                                if ($driverRow = $driverResult->fetch_assoc()) {
                                                    $driverDisplay = htmlspecialchars($driverRow['name']);
                                                }
                                                $driverStmt->close();
                                            }
                                            echo $driverDisplay;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            $assignStatus = strtolower($task['task_assign_status'] ?? 'not assigned');
                                            $statusClass = 'pill assigned';
                                            if ($assignStatus === 'assigned') $statusClass = 'pill assigned';
                                            elseif ($assignStatus === 'processing') $statusClass = 'pill processing';
                                            elseif ($assignStatus === 'ongoing') $statusClass = 'pill ongoing';
                                            elseif ($assignStatus === 'completed') $statusClass = 'pill completed';
                                            elseif ($assignStatus === 'rejected') $statusClass = 'pill rejected';
                                        ?>
                                        <span class="<?= $statusClass ?>"><?= ucfirst($assignStatus) ?></span>
                                    </td>
                                    <td class="actions-col">
                                        <?php
                                            // Reuse $assignStatus defined earlier in the row
                                            $assignStatus = strtolower($task['task_assign_status'] ?? 'not assigned');
                                            $hasDriver = !empty($task['driver_id']);
                                    
                                            // Decide whether assignment/reassign is allowed
                                            $disabled = false;
                                            $disabledLabel = '';
                                            if ($assignStatus === 'completed') {
                                                $disabled = true;
                                                $disabledLabel = 'Completed';
                                            } elseif ($assignStatus === 'ongoing') {
                                                $disabled = true;
                                                $disabledLabel = 'Ongoing';
                                            }
                                    
                                            // Prepare safe values for JS call
                                            $bk = htmlspecialchars($task['booking_id']);
                                            $fn = htmlspecialchars($task['fullName']);
                                            $pk = htmlspecialchars($task['pickupLocation']);
                                            $dst = htmlspecialchars($task['destination']);
                                            $vt = htmlspecialchars($task['vehicleType']);
                                            $pd = htmlspecialchars($task['pickupDate']);
                                        ?>
                                    
                                        <?php if ($disabled): ?>
                                            <button class="action-btn" disabled aria-disabled="true" title="<?= htmlspecialchars($disabledLabel) ?>" style="opacity:0.7;background:#c7c7c7;color:#333;cursor:not-allowed;">
                                                <i class="fas fa-lock"></i> <?= htmlspecialchars($disabledLabel) ?>
                                            </button>
                                    
                                        <?php else: ?>
                                            <?php if ($hasDriver): ?>
                                                <!-- Task already assigned and allowed to be reassigned -->
                                                <button class="action-btn"
                                                    onclick="openAssignModal('<?= $bk ?>', '<?= $fn ?>', '<?= $pk ?>', '<?= $dst ?>', '<?= $vt ?>', '<?= $pd ?>')"
                                                    title="Reassign this task to another driver">
                                                    <i class="fas fa-exchange-alt"></i> Reassign
                                                </button>
                                            <?php else: ?>
                                                <!-- Not assigned yet -->
                                                <button class="action-btn"
                                                    onclick="openAssignModal('<?= $bk ?>', '<?= $fn ?>', '<?= $pk ?>', '<?= $dst ?>', '<?= $vt ?>', '<?= $pd ?>')"
                                                    title="Assign this task to a driver">
                                                    <i class="fas fa-user-plus"></i> Assign
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($assignMessage)): ?>
                <div class="message <?= strpos($assignMessage, 'Error') !== false ? 'message-error' : 'message-success' ?>">
                    <?= htmlspecialchars($assignMessage) ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Assign Modal -->
    <div id="assignModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="assignBackdrop"></div>
        <div class="modal-panel" id="assignPanel">
            <button class="modal-close" id="assignClose" aria-label="Close modal">&times;</button>

            <div id="assignModalContent">
                <h3 id="modalTaskName" style="margin-top:0;">Task name</h3>
                <p class="muted small">Booking ID: <span id="modalTaskId"></span></p>

                <div style="margin-top:0.6rem;">
                    <div class="detail-item">
                        <div class="detail-label">Pickup Location</div>
                        <div class="detail-value" id="modalPickup"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Destination</div>
                        <div class="detail-value" id="modalDestination"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vehicle Type</div>
                        <div class="detail-value" id="modalVehicle"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pickup Date</div>
                        <div class="detail-value" id="modalPickupDate"></div>
                    </div>
                </div>

                <form id="assignForm" method="post" action="assign.php" novalidate>
                    <input type="hidden" name="task_id" id="form_task_id" value="">

                    <label style="display:block; margin-top:12px; font-weight:600;">
                        Assign Driver
                        <select name="driver_id" id="form_driver_id" required>
                            <option value="">-- Select a Driver --</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= htmlspecialchars($driver['id']) ?>">
                                    <?= htmlspecialchars($driver['name']) ?> (<?= htmlspecialchars($driver['status']) ?>) - Tasks: <?= $driver['assigned_tasks'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display:block; margin-top:12px; font-weight:600;">
                        Instructions to Driver (optional)
                        <textarea name="instructions" id="form_instructions" rows="4" placeholder="Add any specific instruction for the driver"></textarea>
                    </label>

                    <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:12px;">
                        <button type="submit" id="assignSubmit" class="btn btn-primary">Assign Driver</button>
                        <button type="button" id="assignCancel" class="btn" onclick="closeAssignModal()" style="background:#eee;color:#222;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAssignModal(bookingId, clientName, pickup, destination, vehicleType, pickupDate) {
            document.getElementById('modalTaskId').textContent = bookingId;
            document.getElementById('modalTaskName').textContent = clientName;
            document.getElementById('modalPickup').textContent = pickup;
            document.getElementById('modalDestination').textContent = destination;
            document.getElementById('modalVehicle').textContent = vehicleType;
            document.getElementById('modalPickupDate').textContent = pickupDate;
            document.getElementById('form_task_id').value = bookingId;
            document.getElementById('form_driver_id').value = '';
            document.getElementById('form_instructions').value = '';

            var modal = document.getElementById('assignModal');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeAssignModal() {
            var modal = document.getElementById('assignModal');
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.getElementById('assignClose').addEventListener('click', closeAssignModal);
        document.getElementById('assignBackdrop').addEventListener('click', closeAssignModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAssignModal();
        });
    </script>
</body>
</html>