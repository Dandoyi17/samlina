<?php
// admin/pending.php
require_once __DIR__ . '/../db/config.php';

// Fetch pending hires
$rows = [];
$totalCount = 0;

$sql = "SELECT
            h.id AS hire_pk,
            h.booking_id AS task_id,
            CONCAT(COALESCE(h.pickupLocation,''), ' → ', COALESCE(h.destination,'')) AS task_name,
            COALESCE(d.name, '') AS driver_name,
            COALESCE(h.fullName, '') AS client,
            COALESCE(h.task_assign_status, h.status, '') AS status,
            (SELECT r.rating FROM ratings r WHERE r.hire_id = h.id LIMIT 1) AS rating,
            COALESCE(h.pickupDate, h.created_at) AS date_display
        FROM hire h
        LEFT JOIN drivers d ON h.driver_id = d.id
        WHERE LOWER(COALESCE(h.task_assign_status, h.status, '')) = 'pending'
        ORDER BY date_display DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $res->free();
    }
    $stmt->close();
}
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Pending Tasks - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/approved.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Minimal table styling fallback in case CSS files are missing */
        .responsive-table { width:100%; border-collapse:collapse; }
        .responsive-table th, .responsive-table td { padding:.6rem .8rem; border-bottom:1px solid #eee; text-align:left; }
        .actions-col { white-space:nowrap; }
        .muted.small { color:#666; font-size:.95rem; }
        .btn { display:inline-block; padding:.4rem .6rem; border-radius:6px; border:0; cursor:pointer; background:#1976d2; color:#fff; text-decoration:none; }
        .btn.ghost { background:#f3f3f3; color:#333; }
    </style>
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Pending Tasks</h1>
            </div>
        </div>
        <div class="header-right controls">
            <div class="search-wrap">
                <input id="searchInput" type="search" placeholder="Search by driver, task name, id or date">
                <button id="searchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                <button id="clearBtn" class="btn ghost"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Pending Tasks</h2>
                <p class="muted small">Showing <span id="totalCount"><?= intval($totalCount) ?></span> pending tasks</p>
            </div>

            <div class="table-wrap">
                <table id="tasksTable" class="responsive-table">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Task Name</th>
                            <th>Assigned Driver</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th>Date</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;padding:1rem;color:#666;">No pending tasks found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['task_id']) ?></td>
                                    <td><?= htmlspecialchars($row['task_name']) ?></td>
                                    <td><?= htmlspecialchars($row['driver_name'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($row['client']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                                    <td>
                                        <?php
                                            if ($row['rating'] === null || $row['rating'] === '') {
                                                echo '<span class="muted small">Not rated</span>';
                                            } else {
                                                echo htmlspecialchars(number_format((float)$row['rating'], 1));
                                            }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['date_display']) ?></td>
                                    <td class="actions-col">
                                        <a class="btn ghost" href="task_detail.php?booking_id=<?= urlencode($row['task_id']) ?>" title="View details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if (!empty($row['driver_name'])): ?>
                                            <a class="btn" style="margin-left:.4rem" href="assign.php?booking_id=<?= urlencode($row['task_id']) ?>" title="Assign / Reassign">
                                                <i class="fas fa-user-plus"></i> Assign
                                            </a>
                                        <?php else: ?>
                                            <a class="btn" style="margin-left:.4rem" href="assign.php?booking_id=<?= urlencode($row['task_id']) ?>" title="Assign driver">
                                                <i class="fas fa-user-plus"></i> Assign
                                            </a>
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

    <!-- Modal -->
    <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="modal-panel" id="modalPanel">
            <button class="modal-close" id="modalClose">&times;</button>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        // small client-side search for the table (filters rows by text)
        (function(){
            const input = document.getElementById('searchInput');
            const btn = document.getElementById('searchBtn');
            const clear = document.getElementById('clearBtn');
            const tbody = document.querySelector('#tasksTable tbody');

            function filterTable(q) {
                q = (q || '').toLowerCase().trim();
                Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
                    if (!q) { tr.style.display=''; return; }
                    const text = tr.textContent.toLowerCase();
                    tr.style.display = text.indexOf(q) !== -1 ? '' : 'none';
                });
            }

            btn.addEventListener('click', () => filterTable(input.value));
            clear.addEventListener('click', () => { input.value=''; filterTable(''); });
            input.addEventListener('keydown', (e)=> { if (e.key === 'Enter') filterTable(input.value); });
        })();
    </script>

</body>

</html>