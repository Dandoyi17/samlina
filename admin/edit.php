<?php
require_once '../db/config.php';

// ---------- UPDATE HANDLER ----------
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
    $stmt = null;
    if ($hasRating) {
        $ratingRaw = $_POST['rating'] ?? '';
        $rating = ($ratingRaw === '' ? null : floatval($ratingRaw));

        if ($rating === null) {
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
            header('Location: edit.php');
            exit;
        } else {
            $editMessage = 'Update failed: ' . $stmt->error;
            $stmt->close();
        }
    }
}
// ---------- END UPDATE HANDLER ----------

// Fetch drivers list
$drivers = [];
$res = $conn->query("SHOW TABLES LIKE 'drivers'");
if ($res && $res->num_rows) {
    $result = $conn->query("SELECT * FROM drivers ORDER BY id");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $drivers[] = $r;
        }
    }
}

// If edit requested via query param, load driver data to populate modal
$editDriver = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    foreach ($drivers as $d) {
        if ((string)$d['id'] === (string)$edit_id) {
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
    <link rel="stylesheet" href="css/drivers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modal styles */
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
        .edit-modal-panel {
            background: #fff;
            border-radius: 10px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow: auto;
            position: relative;
            padding: 1.25rem 1.25rem 5.25rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.18);
            -webkit-overflow-scrolling: touch;
            outline: none;
        }
        .edit-modal-close {
            position: absolute;
            right: 0.6rem;
            top: 0.4rem;
            background: transparent;
            border: 0;
            font-size: 1.6rem;
            cursor: pointer;
            line-height: 1;
            color: #6c757d;
        }
        .edit-modal-panel h2 {
            margin-top: 0;
            font-size: 1.4rem;
            color: #222;
        }
        .edit-modal-panel label {
            display: block;
            font-weight: 600;
            color: #222;
            margin-bottom: 0.35rem;
            margin-top: 0.75rem;
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
            font-family: inherit;
        }
        .edit-modal-panel textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-grid {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .form-grid > div {
            flex: 1;
            min-width: 260px;
        }
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
            max-width: 900px;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
            align-items: center;
        }
        .btn {
            padding: 0.6rem 0.9rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            border: 0;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn.primary {
            background: #19496c;
            color: #fff;
        }
        .btn.secondary {
            background: #eee;
            color: #222;
        }
        .btn:hover {
            opacity: 0.9;
        }
        @media (max-width: 700px) {
            .edit-modal-inner-footer {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 0.5rem;
            }
            .btn {
                justify-content: center;
            }
            .form-grid {
                flex-direction: column;
            }
        }
        .image-preview.small img {
            max-width: 120px;
            border-radius: 6px;
            display: block;
            margin-top: 0.5rem;
        }
        .success-msg {
            color: #0b7a47;
            font-weight: 600;
            margin-top: 1rem;
        }
        .error-msg {
            color: #d32f2f;
            font-weight: 600;
            margin-top: 1rem;
        }
        /* Table styles */
        .responsive-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        .responsive-table thead th {
            text-align: left;
            padding: 0.8rem;
            background: #fbfbfb;
            font-size: 0.92rem;
            position: sticky;
            top: 0;
            z-index: 2;
            border-bottom: 1px solid #eee;
        }
        .responsive-table tbody td {
            padding: 0.7rem 0.8rem;
            border-bottom: 1px solid #f2f3f4;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .actions-col {
            width: 160px;
            white-space: nowrap;
        }
        .action-btn.edit {
            display: inline-flex;
            gap: 0.45rem;
            align-items: center;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            background: #19496c;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            border: 0;
            cursor: pointer;
        }
        .pill {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            color: #fff;
            font-weight: 700;
            display: inline-block;
        }
        .pill.online {
            background: #0b7a47;
        }
        .pill.offline {
            background: #b71c1c;
        }
        .pill.pending {
            background: #be985b;
        }
        .page-container {
            padding: 1.25rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.04);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f2f3;
        }
        .card-title {
            margin: 0;
            font-size: 1.05rem;
        }
        .muted {
            color: #6c757d;
        }
        .muted.small {
            font-size: 0.85rem;
        }
        .table-wrap {
            overflow: auto;
            max-height: 60vh;
            padding-top: 0.5rem;
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

                        <h2>Edit Driver — <?= htmlspecialchars($editDriver['name']) ?> <small class="muted">(<?= htmlspecialchars($editDriver['id']) ?>)</small></h2>

                        <div class="form-grid">
                            <div>
                                <label>Driver ID<br><input name="driver_id" type="text" value="<?= htmlspecialchars($editDriver['id']) ?>" required></label>

                                <label>Full Name<br><input name="name" type="text" value="<?= htmlspecialchars($editDriver['name']) ?>" required></label>

                                <label>Email<br><input name="email" type="email" value="<?= htmlspecialchars($editDriver['email'] ?? '') ?>"></label>

                                <label>Phone<br><input name="phone" type="tel" value="<?= htmlspecialchars($editDriver['phone'] ?? '') ?>"></label>

                                <label>Username<br><input name="username" type="text" value="<?= htmlspecialchars($editDriver['username'] ?? '') ?>"></label>

                                <label>Address<br><textarea name="address"><?= htmlspecialchars($editDriver['address'] ?? '') ?></textarea></label>
                            </div>

                            <div>
                                <label>Vehicle<br><input name="vehicle" type="text" value="<?= htmlspecialchars($editDriver['vehicle'] ?? '') ?>"></label>

                                <label>License No<br><input name="license_no" type="text" value="<?= htmlspecialchars($editDriver['license_no'] ?? '') ?>"></label>

                                <?php 
                                    // Check if rating column exists
                                    $hasRatingCol = false;
                                    $colChk = $conn->query("SHOW COLUMNS FROM `drivers` LIKE 'rating'");
                                    if ($colChk && $colChk->num_rows > 0) $hasRatingCol = true;
                                ?>
                                <?php if ($hasRatingCol): ?>
                                    <label>Rating<br><input name="rating" type="number" step="0.1" min="0" max="5" value="<?= htmlspecialchars($editDriver['rating'] ?? '') ?>"></label>
                                <?php endif; ?>

                                <label>Status<br>
                                    <select name="status">
                                        <option value="online" <?= (isset($editDriver['status']) && $editDriver['status']=='online') ? 'selected' : '' ?>>Online</option>
                                        <option value="active" <?= (isset($editDriver['status']) && $editDriver['status']=='active') ? 'selected' : '' ?>>Active</option>
                                        <option value="free to work" <?= (isset($editDriver['status']) && $editDriver['status']=='free to work') ? 'selected' : '' ?>>Free to Work</option>
                                        <option value="engaged" <?= (isset($editDriver['status']) && $editDriver['status']=='engaged') ? 'selected' : '' ?>>Engaged</option>
                                        <option value="not available" <?= (isset($editDriver['status']) && $editDriver['status']=='not available') ? 'selected' : '' ?>>Not Available</option>
                                        <option value="offline" <?= (isset($editDriver['status']) && $editDriver['status']=='offline') ? 'selected' : '' ?>>Offline</option>
                                        <option value="pending" <?= (isset($editDriver['status']) && $editDriver['status']=='pending') ? 'selected' : '' ?>>Pending</option>
                                    </select>
                                </label>

                                <label>Notes<br><textarea name="notes"><?= htmlspecialchars($editDriver['notes'] ?? '') ?></textarea></label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sticky footer with Save/Close buttons -->
                <div class="edit-modal-footer" aria-hidden="false">
                    <div class="edit-modal-inner-footer">
                        <button type="button" class="btn primary" onclick="submitEditForm()">Save Changes</button>
                        <a href="edit.php" class="btn secondary" role="button">Close</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($editMessage && !$editDriver): ?>
            <div class="<?= strpos($editMessage, 'failed') !== false || strpos($editMessage, 'Prepare') !== false ? 'error-msg' : 'success-msg' ?>">
                <?= htmlspecialchars($editMessage) ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        /* Submit the visible edit form in the modal */
        function submitEditForm() {
            var form = document.getElementById('editForm');
            if (!form) {
                alert('Edit form not found.');
                return;
            }
            form.submit();
        }

        /* Allow Esc to close modal */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') window.location = 'edit.php';
        });
    </script>
</body>
</html>