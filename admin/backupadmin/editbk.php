<?php
require_once '../db/config.php';

// // Handle update
$editMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['original_id'])) {
    $original_id = $_POST['original_id'];
    $driver_id   = $_POST['driver_id'] ?? $original_id;
    $name        = $_POST['name'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $username    = $_POST['username'] ?? '';
    $address     = $_POST['address'] ?? '';
    $vehicle     = $_POST['vehicle'] ?? '';
    $license_no  = $_POST['license_no'] ?? '';
    $status      = $_POST['status'] ?? 'pending';
    $notes       = $_POST['notes'] ?? '';

    // Detect whether 'rating' column exists in drivers table
    $hasRating = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM `drivers` LIKE 'rating'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $hasRating = true;
    }

    // Build SQL and bind params depending on presence of rating
    if ($hasRating) {
        // If rating is provided, cast to float; if empty, set to NULL
        $ratingRaw = $_POST['rating'] ?? '';
        $rating = ($ratingRaw === '' ? null : floatval($ratingRaw));

        if ($rating === null) {
            // rating column exists but user left it empty - set to NULL
            $sql = "UPDATE drivers SET id=?, name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, rating=NULL, status=?, notes=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssssssss", $driver_id, $name, $email, $phone, $username, $address, $vehicle, $license_no, $status, $notes, $original_id);
            }
        } else {
            $sql = "UPDATE drivers SET id=?, name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, rating=?, status=?, notes=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssssssssdsss", $driver_id, $name, $email, $phone, $username, $address, $vehicle, $license_no, $rating, $status, $notes, $original_id);
            }
        }
    } else {
        // No rating column
        $sql = "UPDATE drivers SET id=?, name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, status=?, notes=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssssss", $driver_id, $name, $email, $phone, $username, $address, $vehicle, $license_no, $status, $notes, $original_id);
        }
    }

    if (!$stmt) {
        $editMessage = 'Prepare failed: ' . $conn->error;
    } else {
        if ($stmt->execute()) {
            $stmt->close();
            // redirect to clear query params and avoid resubmit on refresh
            header('Location: edit.php');
            exit;
        } else {
            $editMessage = 'Update failed: ' . $stmt->error;
            $stmt->close();
        }
    }
}
// $editMessage = '';
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['original_id'])) {
//     $stmt = $conn->prepare("UPDATE drivers SET id=?, name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, rating=?, status=? WHERE id=?");
//     $stmt->bind_param(
//         "sssssssssss",
//         $_POST['driver_id'],
//         $_POST['name'],
//         $_POST['email'],
//         $_POST['phone'],
//         $_POST['username'],
//         $_POST['address'],
//         $_POST['vehicle'],
//         $_POST['license_no'],
//         $_POST['rating'],
//         $_POST['status'],
//         $_POST['original_id']
//     );
//     if ($stmt->execute()) {
//         $editMessage = "Driver updated successfully!";
//     } else {
//         $editMessage = "Error updating driver: " . $stmt->error;
//     }
// }

// Fetch all drivers
$drivers = [];
$result = $conn->query("SELECT * FROM drivers ORDER BY id");
while ($row = $result->fetch_assoc()) {
    $drivers[] = $row;
}

// If editing, fetch that driver
$editDriver = null;
if (isset($_GET['edit_id'])) {
    foreach ($drivers as $d) {
        if ($d['id'] == $_GET['edit_id']) {
            $editDriver = $d;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Edit Driver - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/edit.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>

        /* edit.css - styles for edit modal, form and responsive table */
    
    /* Use the project's variables if available */
    
    /* Table small improvements */
    .responsive-table th, .responsive-table td {
      padding: 0.7rem 0.9rem;
      vertical-align: middle;
      font-size: 0.95rem;
    }
    
    .actions-col { width: 160px; white-space: nowrap; }
    
    .action-btn.edit {
      display: inline-flex;
      gap: .45rem;
      align-items: center;
      padding: .35rem .6rem;
      border-radius: 6px;
      background: var(--primary);
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      border: 0;
    }
    
    /* Status pill (keeps consistent look with drivers.css) */
    .pill { font-size: .85rem; padding: .25rem .5rem; border-radius: 12px; color:#fff; font-weight:700; display:inline-block; }
    .pill.online { background: #0b7a47; }
    .pill.offline { background: #b71c1c; }
    .pill.pending { background: var(--accent); }
    
    /* Modal overlay (centers and dims background) */
    .edit-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1200;
      padding: 1rem;
    }
    
    /* Modal panel: scrollable content, visually elevated */
    .edit-modal-panel {
      background: var(--white);
      border-radius: var(--radius);
      width: 100%;
      max-width: var(--modal-max-width);
      max-height: 90vh;
      overflow: auto;               /* allow inner scrolling */
      position: relative;
      padding: 1.25rem 1.25rem 5.25rem; /* additional bottom padding so content isn't covered by sticky footer */
      box-shadow: 0 10px 40px rgba(0,0,0,0.18);
      -webkit-overflow-scrolling: touch;
      outline: none;
    }
    
    /* Close button in top-right of panel */
    .edit-modal-close {
      position: absolute;
      right: 0.6rem;
      top: 0.4rem;
      background: transparent;
      border: 0;
      font-size: 1.6rem;
      cursor: pointer;
      line-height: 1;
      color: var(--muted);
    }
    
    /* Form field layout within modal */
    .edit-modal-panel form {
      font-size: 0.95rem;
    }
    
    .edit-modal-panel label {
      display: block;
      font-weight: 600;
      color: #222;
      margin-bottom: 0.35rem;
    }
    
    .edit-modal-panel input[type="text"],
    .edit-modal-panel input[type="email"],
    .edit-modal-panel input[type="tel"],
    .edit-modal-panel input[type="number"],
    .edit-modal-panel select,
    .edit-modal-panel textarea {
      width: 100%;
      padding: 0.5rem 0.6rem;
      border: 1px solid #e3e6ea;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 0.95rem;
      margin-top: 0.25rem;
    }
    
    /* Two-column responsive form grid */
    @media (min-width: 720px) {
      .edit-modal-panel .form-columns {
        display: flex;
        gap: 1.25rem;
      }
      .edit-modal-panel .form-column { flex: 1; min-width: 260px; }
    }
    @media (max-width: 719px) {
      .edit-modal-panel .form-columns { display: block; }
    }
    
    /* Image preview */
    .image-preview.small img { max-width: 120px; border-radius: 6px; display:block; }
    
    /* Sticky footer with Save button (always visible) */
    .edit-modal-footer {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      justify-content: center;
      padding: 0.8rem;
      background: rgba(255,255,255,0.98);
      box-shadow: 0 -8px 24px rgba(0,0,0,0.06);
      z-index: 1300;
    }
    
    .edit-modal-inner-footer {
      width: 100%;
      max-width: var(--modal-max-width);
      display: flex;
      gap: 0.75rem;
      justify-content: flex-start;
      align-items: center;
    }
    
    /* Footer buttons */
    .edit-modal-inner-footer .btn {
      padding: .6rem .9rem;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      border: 0;
    }
    
    .edit-modal-inner-footer .btn.primary {
      background: var(--primary);
      color: #fff;
    }
    
    .edit-modal-inner-footer .btn.secondary {
      background: #eee;
      color: #222;
    }
    
    /* Layout adjustments for very small screens: stack buttons */
    @media (max-width:700px) {
      .edit-modal-inner-footer { flex-direction: column-reverse; align-items: stretch; gap: .5rem; padding: .25rem 0; }
      .edit-modal-footer { padding: .5rem; }
    }
    
    /* Accessibility / focus */
    .edit-modal-panel:focus { outline: 2px solid rgba(25,73,108,0.12); }
    
    /* Small tweaks so long field labels don't cause overflow */
    .edit-modal-panel label { word-break: break-word; }
    
    /* Success / error message */
    .edit-message {
      margin-top: .75rem;
      font-weight: 600;
      color: #1976d2;
    }
    
    /* Ensure the table wrap does not overflow the page */
    .table-wrap { max-height: 60vh; overflow: auto; }
    
    /* Minor responsive adjustments for table on smaller widths */
    @media (max-width:640px) {
      .responsive-table { font-size: .92rem; }
      .actions-col { width: 120px; }
    }
    </style>



</head>
<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Edit Driver</h1>
            </div>
        </div>
    </header>
        <main class="page-container">
        <section class="card">
            <div class="card-header" style="align-items:center;">
                <div>
                    <h2 class="card-title">All Drivers</h2>
                    <p class="muted small">Click Edit to update a driver</p>
                </div>
            </div>
    
            <div class="table-wrap" id="resultsWrap" style="margin-top:0.75rem;">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>Driver ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drivers)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:1rem;color:#666;">No drivers found in database</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drivers as $driver): ?>
                                <tr>
                                    <td><?= htmlspecialchars($driver['id']) ?></td>
                                    <td><?= htmlspecialchars($driver['name']) ?></td>
                                    <td><?= htmlspecialchars($driver['phone']) ?></td>
                                    <td><?= htmlspecialchars($driver['vehicle']) ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($driver['status'] ?? '');
                                            $cls = 'pill pending';
                                            if ($st === 'online' || $st === 'active' || $st === 'free to work') $cls = 'pill online';
                                            elseif ($st === 'offline' || $st === 'not available') $cls = 'pill offline';
                                            elseif ($st === 'engaged') $cls = 'pill pending';
                                        ?>
                                        <span class="<?= $cls ?>"><?= htmlspecialchars(ucfirst($driver['status'] ?? '—')) ?></span>
                                    </td>
                                    <td class="actions-col">
                                        <a href="?edit_id=<?= urlencode($driver['id']) ?>" class="action-btn edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    
        <?php if ($editDriver): ?>
            <!-- Modal overlay -->
            <div class="edit-modal-overlay" role="dialog" aria-modal="true">
                <div class="edit-modal-panel" role="document" tabindex="-1">
                    <button class="edit-modal-close" title="Close" onclick="window.location='edit.php'">&times;</button>
    
                    <form id="editForm" method="post" action="edit.php" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($editDriver['id']) ?>">
    
                        <h2 style="margin-top:0;">Edit Driver — <?= htmlspecialchars($editDriver['name']) ?> <small class="muted">(<?= htmlspecialchars($editDriver['id']) ?>)</small></h2>
    
                        <div style="display:flex;gap:1.25rem;flex-wrap:wrap;">
                            <div style="flex:1;min-width:260px;">
                                <label>Driver ID<br><input name="driver_id" type="text" value="<?= htmlspecialchars($editDriver['id']) ?>" required></label><br><br>
    
                                <label>Full Name<br><input name="name" type="text" value="<?= htmlspecialchars($editDriver['name']) ?>" required></label><br><br>
    
                                <label>Email<br><input name="email" type="email" value="<?= htmlspecialchars($editDriver['email'] ?? '') ?>"></label><br><br>
    
                                <label>Phone<br><input name="phone" type="tel" value="<?= htmlspecialchars($editDriver['phone'] ?? '') ?>"></label><br><br>
    
                                <label>Username<br><input name="username" type="text" value="<?= htmlspecialchars($editDriver['username'] ?? '') ?>"></label><br><br>
    
                                <label>Address<br><textarea name="address" rows="3"><?= htmlspecialchars($editDriver['address'] ?? '') ?></textarea></label><br>
                            </div>
    
                            <div style="flex:1;min-width:260px;">
                                <label>Vehicle<br><input name="vehicle" type="text" value="<?= htmlspecialchars($editDriver['vehicle'] ?? '') ?>"></label><br><br>
    
                                <label>License No<br><input name="license_no" type="text" value="<?= htmlspecialchars($editDriver['license_no'] ?? '') ?>"></label><br><br>
    
                                <label>Rating<br><input name="rating" type="number" step="0.1" min="0" max="5" value="<?= htmlspecialchars($editDriver['rating'] ?? '') ?>"></label><br><br>
    
                                <label>Status<br>
                                    <select name="status">
                                        <option value="online" <?= (isset($editDriver['status']) && $editDriver['status']=='online') ? 'selected' : '' ?>>Online</option>
                                        <option value="free to work" <?= (isset($editDriver['status']) && $editDriver['status']=='free to work') ? 'selected' : '' ?>>Free to Work</option>
                                        <option value="engaged" <?= (isset($editDriver['status']) && $editDriver['status']=='engaged') ? 'selected' : '' ?>>Engaged</option>
                                        <option value="not available" <?= (isset($editDriver['status']) && $editDriver['status']=='not available') ? 'selected' : '' ?>>Not Available</option>
                                        <option value="offline" <?= (isset($editDriver['status']) && $editDriver['status']=='offline') ? 'selected' : '' ?>>Offline</option>
                                        <option value="pending" <?= (isset($editDriver['status']) && $editDriver['status']=='pending') ? 'selected' : '' ?>>Pending</option>
                                    </select>
                                </label><br><br>
    
                                <label>Profile Image (replace)<br>
                                    <input id="profile_image" name="profile_image" type="file" accept="image/*">
                                    <div class="image-preview small" id="profilePreview" style="margin-top:0.5rem;">
                                        <?php if (!empty($editDriver['profile'])): ?>
                                            <img src="../<?= htmlspecialchars($editDriver['profile']) ?>" alt="Preview" style="max-width:120px;border-radius:6px;">
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        </div>
    
                        <div style="margin-top:1rem;">
                            <?php if (!empty($editDriver['notes'])): ?>
                                <label>Notes<br><textarea name="notes" rows="3"><?= htmlspecialchars($editDriver['notes']) ?></textarea></label>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
    
                <!-- Sticky footer with Save/Cancel buttons -->
                <div class="edit-modal-footer" aria-hidden="false">
                    <div class="edit-modal-inner-footer">
                        <form method="post" action="edit.php" style="margin:0;display:flex;gap:.75rem;">
                            <!-- include the same fields needed for update; we'll include original_id and move values via JS when available -->
                            <input type="hidden" name="original_id" value="<?= htmlspecialchars($editDriver['id']) ?>">
                            <input type="hidden" name="driver_id" value="<?= htmlspecialchars($editDriver['id']) ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($editDriver['name']) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($editDriver['email'] ?? '') ?>">
                            <input type="hidden" name="phone" value="<?= htmlspecialchars($editDriver['phone'] ?? '') ?>">
                            <input type="hidden" name="username" value="<?= htmlspecialchars($editDriver['username'] ?? '') ?>">
                            <input type="hidden" name="address" value="<?= htmlspecialchars($editDriver['address'] ?? '') ?>">
                            <input type="hidden" name="vehicle" value="<?= htmlspecialchars($editDriver['vehicle'] ?? '') ?>">
                            <input type="hidden" name="license_no" value="<?= htmlspecialchars($editDriver['license_no'] ?? '') ?>">
                            <input type="hidden" name="rating" value="<?= htmlspecialchars($editDriver['rating'] ?? '') ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($editDriver['status'] ?? 'pending') ?>">
                            <button type="submit" class="btn primary">Save Changes</button>
                        </form>
                        <a href="edit.php" class="btn" style="background:#eee;">Close</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    
        <?php if ($editMessage && !$editDriver): ?>
            <div style="margin-top:1rem;color:#1976d2;"><?= htmlspecialchars($editMessage) ?></div>
        <?php endif; ?>
    </main>
    
    <script>
/* Submit the visible edit form in the modal. This avoids duplicate forms/hidden fields. */
function submitEditForm(){
    var form = document.getElementById('editForm');
    if (!form) {
        alert('Edit form not found.');
        return;
    }
    // Before submit, ensure any dynamic or client-side edited values are present in form fields.
    form.submit();
}

// allow Esc to close modal (navigate back to edit.php)
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') window.location = 'edit.php';
});
</script>
</body>
</html>