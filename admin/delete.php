<?php
// admin/delete.php
session_start();
require_once __DIR__ . '/../db/config.php';

// Fetch all drivers from the database
$drivers = [];
$sql = "SELECT id, driver_id, name, phone, vehicle, status FROM drivers ORDER BY name ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $drivers[] = $row;
        }
        $res->free();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Delete Drivers - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/delete.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Delete A Drivers</h1>

            </div>
        </div>
        <div class="header-right">
            <button class="back-btn" onclick="location.href='drivers.php'"><i class="fas fa-arrow-left"></i> Back</button>
        </div>
    </header>

        <main class="page-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background:#c8e6c9;color:#2e7d32;padding:.8rem;border-radius:6px;margin-bottom:1rem;">
                <strong>✓ Success:</strong> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background:#ffcdd2;color:#b71c1c;padding:.8rem;border-radius:6px;margin-bottom:1rem;">
                <strong>✗ Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <section class="card">
            <div class="card-header" style="align-items:center;">
                <div>
                    <h2 class="card-title">Drivers</h2>
                    <p class="muted small">Search by name or Driver ID, select rows, and delete</p>
                </div>
                <div style="margin-left:auto;display:flex;gap:0.6rem;align-items:center;">
                    <input id="searchInput" type="search" placeholder="Search by name or ID">
                    <button id="searchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                    <button id="resetBtn" class="btn ghost"><i class="fas fa-times"></i></button>
                    <button id="deleteSelectedBtn" class="btn danger" disabled><i class="fas fa-trash"></i> Delete Selected</button>
                </div>
            </div>

            <form id="bulkDeleteForm" method="post" action="delete_driver.php">
                <input type="hidden" name="delete_ids" id="delete_ids" value="">
                <div class="table-wrap" style="margin-top:0.75rem;">
                    <table id="driversTable" class="responsive-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Driver ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                                            
                                                <tbody id="driversTbody">
                                                    <?php if (empty($drivers)): ?>
                                                        <tr>
                                                            <td colspan="7" style="text-align:center;padding:1rem;color:#666;">No drivers found</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($drivers as $driver): ?>
                                                            <tr>
                                                                <td><input type="checkbox" class="driver-checkbox" value="<?= intval($driver['id']) ?>"></td>
                                                                <td><?= htmlspecialchars($driver['driver_id']) ?></td>
                                                                <td><?= htmlspecialchars($driver['name']) ?></td>
                                                                <td><?= htmlspecialchars($driver['phone']) ?></td>
                                                                <td><?= htmlspecialchars($driver['vehicle']) ?></td>
                                                                <td><?= htmlspecialchars($driver['status']) ?></td>
                                                                <td class="actions-col">
                                                                    <button type="button" class="btn danger" style="padding:.4rem .6rem;font-size:.9rem;" onclick="confirmDelete(<?= intval($driver['id']) ?>, '<?= addslashes(htmlspecialchars($driver['name'])) ?>')">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                    </table>
                </div>
            </form>
        </section>
    </main>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="confirmBackdrop"></div>
        <div class="modal-panel" id="confirmPanel">
            <button class="modal-close" id="confirmClose">&times;</button>
            <div id="confirmContent"></div>
        </div>
    </div>

    <!-- <script src="js/delete.js"></script> -->

        
        <script>
            // Select All checkbox
            document.getElementById('selectAll')?.addEventListener('change', function(){
                const checkboxes = document.querySelectorAll('.driver-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateDeleteBtn();
            });
    
            // Update delete button state when individual checkboxes change
            document.querySelectorAll('.driver-checkbox').forEach(cb => {
                cb.addEventListener('change', updateDeleteBtn);
            });
    
            function updateDeleteBtn() {
                const checked = document.querySelectorAll('.driver-checkbox:checked').length;
                document.getElementById('deleteSelectedBtn').disabled = (checked === 0);
            }
    
            // Search functionality
            document.getElementById('searchBtn')?.addEventListener('click', filterTable);
            document.getElementById('resetBtn')?.addEventListener('click', () => {
                document.getElementById('searchInput').value = '';
                filterTable();
            });
            document.getElementById('searchInput')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') filterTable();
            });
    
            function filterTable() {
                const q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
                const rows = document.querySelectorAll('#driversTbody tr:not(:first-child)');
                rows.forEach(tr => {
                    const text = tr.textContent.toLowerCase();
                    tr.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
                });
            }
    
            // Bulk delete handler
            document.getElementById('deleteSelectedBtn')?.addEventListener('click', (e) => {
                e.preventDefault();
                const checked = document.querySelectorAll('.driver-checkbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one driver to delete.');
                    return;
                }
                const names = Array.from(checked).map(cb => cb.closest('tr').cells[2].textContent).join(', ');
                if (confirm(`Delete ${checked.length} driver(s): ${names}?\n\nThis action cannot be undone.`)) {
                    const ids = Array.from(checked).map(cb => cb.value).join(',');
                    document.getElementById('delete_ids').value = ids;
                    document.getElementById('bulkDeleteForm').submit();
                }
            });
    
            // Individual delete
            function confirmDelete(driverId, driverName) {
                if (confirm(`Delete driver "${driverName}"?\n\nThis action cannot be undone.`)) {
                    document.getElementById('delete_ids').value = driverId;
                    document.getElementById('bulkDeleteForm').submit();
                }
            }
        </script>
   
</body>

</html>