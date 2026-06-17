<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Approved Tasks - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/approved.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Approved Tasks</h1>

            </div>
        </div>
        <div class="header-right controls">
            <div class="search-wrap">
                <input id="searchInput" type="search" placeholder="Search by driver, task name or date" aria-label="Search tasks">
                <button id="searchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                <button id="clearSearchBtn" class="btn ghost" title="Reset search"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Tasks List</h2>
                <p class="muted small">Showing <span id="totalCount">0</span> approved tasks</p>
            </div>

                                       <?php
                        require_once '../db/config.php';
                        
                        // Load approved hires with driver info and attempt to include latest assignment_history values.
                        // If the richer query fails (missing columns in assignment_history), fall back to a safe query.
                        $tasks = [];
                        
                        // Rich query — attempts to fetch latest assignment_history columns via correlated subqueries
                        $sql = "
                        SELECT
                          h.id AS hire_id,
                          h.booking_id,
                          h.fullName AS client_name,
                          h.email AS client_email,
                          h.phone AS client_phone,
                          h.vehicleType,
                          h.pickupLocation,
                          h.destination,
                          h.pickupDate,
                          h.created_at AS booking_created_at,
                          COALESCE(LOWER(h.task_assign_status), '') AS task_assign_status,
                          h.status AS hire_status,
                          d.id AS driver_id,
                          d.driver_id AS driver_code,
                          d.name AS driver_name,
                          d.phone AS driver_phone,
                          d.rating AS driver_rating,
                          d.profile_image AS driver_image,
                          -- latest assignment history fields (may not exist on every schema)
                          (SELECT ah.assigned_at FROM assignment_history ah WHERE ah.hire_id = h.id ORDER BY ah.id DESC LIMIT 1) AS last_assigned_at,
                          (SELECT ah.instructions FROM assignment_history ah WHERE ah.hire_id = h.id ORDER BY ah.id DESC LIMIT 1) AS last_assignment_instructions
                        FROM hire h
                        LEFT JOIN drivers d ON h.driver_id = d.id
                        WHERE h.status = 'approved'
                        ORDER BY h.created_at DESC
                        ";
                        
                        $res = $conn->query($sql);
                        
                        // If the rich query failed (unknown column or other DB error), fall back to a safer query without assignment_history
                        if (!$res) {
                            // Optional: log $conn->error somewhere or expose it to a debug page if needed
                            $sql_safe = "
                            SELECT
                              h.id AS hire_id,
                              h.booking_id,
                              h.fullName AS client_name,
                              h.email AS client_email,
                              h.phone AS client_phone,
                              h.vehicleType,
                              h.pickupLocation,
                              h.destination,
                              h.pickupDate,
                              h.created_at AS booking_created_at,
                              COALESCE(LOWER(h.task_assign_status), '') AS task_assign_status,
                              h.status AS hire_status,
                              d.id AS driver_id,
                              d.driver_id AS driver_code,
                              d.name AS driver_name,
                              d.phone AS driver_phone,
                              d.rating AS driver_rating,
                              d.profile_image AS driver_image
                            FROM hire h
                            LEFT JOIN drivers d ON h.driver_id = d.id
                            WHERE h.status = 'approved'
                            ORDER BY h.created_at DESC
                            ";
                            $res = $conn->query($sql_safe);
                        }
                        
                        if ($res) {
                            while ($row = $res->fetch_assoc()) {
                                $tasks[] = $row;
                            }
                            // no need to explicitly free for small resultsets, but you may if desired:
                            // $res->free();
                        }
                        ?>

            <div class="table-wrap" id="tableWrap">
                <table id="tasksTable" class="responsive-table">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Task Name</th>
                            <th>Assigned Driver</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Client Rating</th>
                            <th>Date</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                                      
                    <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:1rem;color:#666;">No approved tasks found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $t): 
                            // Prepare safe values and computed labels
                            $bookingId = htmlspecialchars($t['booking_id'] ?? '');
                            $taskName = htmlspecialchars($t['vehicleType'] ?? $t['client_name'] ?? '');
                            $driverName = $t['driver_name'] ? htmlspecialchars($t['driver_name']) : 'Not Assigned';
                            $client = htmlspecialchars($t['client_name'] ?? '');
                            $status = htmlspecialchars(ucfirst($t['task_assign_status'] ?? $t['hire_status'] ?? ''));
                            $driverRating = $t['driver_rating'] !== null ? number_format((float)$t['driver_rating'],1) : '-';
                            $date = !empty($t['booking_created_at']) ? htmlspecialchars(date('Y-m-d', strtotime($t['booking_created_at']))) : '-';
                            $lastAssigned = !empty($t['last_assigned_at']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($t['last_assigned_at']))) : '-';
                            $lastAssignmentStatus = !empty($t['last_assignment_status']) ? htmlspecialchars(ucfirst($t['last_assignment_status'])) : '-';
                        ?>
                        <tr>
                            <td><?= $bookingId ?></td>
                            <td><?= $taskName ?></td>
                            <td>
                                <?php if ($t['driver_id']): ?>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <?php if (!empty($t['driver_image'])): ?>
                                            <img src="<?= htmlspecialchars($t['driver_image']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:700;"><?= $driverName ?></div>
                                            <div class="muted small"><?= htmlspecialchars($t['driver_code'] ?? '') ?> <?= !empty($t['driver_phone']) ? '· ' . htmlspecialchars($t['driver_phone']) : '' ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:700;"><?= $client ?></div>
                                <div class="muted small"><?= htmlspecialchars($t['client_phone'] ?? $t['client_email'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php
                                    // status pill styling (re-uses classes in your CSS)
                                    $s = strtolower(trim($t['task_assign_status'] ?? $t['hire_status'] ?? ''));
                                    $cls = 'pill assigned';
                                    if ($s === 'assigned') $cls = 'pill assigned';
                                    elseif ($s === 'processing') $cls = 'pill processing';
                                    elseif ($s === 'ongoing') $cls = 'pill ongoing';
                                    elseif ($s === 'completed') $cls = 'pill completed';
                                    elseif ($s === 'rejected') $cls = 'pill rejected';
                                ?>
                                <span class="<?= $cls ?>"><?= ucfirst($s ?: 'Approved') ?></span>
                                <?php if ($lastAssigned !== '-'): ?>
                                    <div class="muted small" style="margin-top:.25rem;">Last assigned: <?= $lastAssigned ?> (<?= $lastAssignmentStatus ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td><?= $driverRating ?></td>
                            <td><?= $date ?></td>
                            <td class="actions-col">
                                <!-- View details button -->
                                <button class="action-btn" onclick="openDetail('<?= $bookingId ?>')">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                <!-- If not completed/ongoing, show Assign/Reassign button -->
                                <?php if (!in_array($s, ['completed','ongoing'])): ?>
                                    <button class="action-btn" onclick="openAssignModal('<?= $bookingId ?>', '<?= $taskName ?>', '<?= htmlspecialchars($t['pickupLocation'] ?? '') ?>', '<?= htmlspecialchars($t['destination'] ?? '') ?>', '<?= htmlspecialchars($t['vehicleType'] ?? '') ?>', '<?= htmlspecialchars($t['pickupDate'] ?? '') ?>')">
                                        <i class="fas fa-user-plus"></i> <?= $t['driver_id'] ? 'Reassign' : 'Assign' ?>
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" disabled style="opacity:.7;background:#c7c7c7;color:#333;cursor:not-allowed;">
                                        <i class="fas fa-lock"></i> <?= ucfirst($s) ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="modal-panel" id="modalPanel">
            <button class="modal-close" id="modalClose" aria-label="Close modal">&times;</button>
            <div id="modalContent">
                <!-- filled by JS -->
            </div>
        </div>
    </div>

    <!-- <script src="js/approved.js"></script> -->
</body>

</html>