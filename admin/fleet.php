<?php
// admin/fleet.php
session_start();
require_once __DIR__ . '/../db/config.php';

// Fetch fleet rows
$fleet = [];
$stmt = $conn->prepare("SELECT * FROM fleet ORDER BY created_at DESC");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($r = $res->fetch_assoc()) $fleet[] = $r;
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
    <title>Fleet Management - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/fleet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Fleet Management</h1>
                <!-- <p class="muted">Add, edit and remove vehicles; upload up to 3 images</p> -->
            </div>
        </div>
        <div class="header-right">
            <button class="back-btn" onclick="location.href='dashboard.php'"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header" style="align-items:center;">
                <div>
                    <h2 class="card-title">Fleet</h2>
                    <p class="muted small">Manage fleet cars — add new vehicles or edit existing ones</p>
                </div>

                <div style="margin-left:auto; display:flex; gap:.6rem; align-items:center;">
                    <input id="fleetSearch" type="search" placeholder="Search by model, make, seats, or id" />
                    <button id="fleetSearchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                    <button id="fleetClearBtn" class="btn ghost"><i class="fas fa-times"></i></button>
                    <button id="addFleetBtn" class="btn accent"><i class="fas fa-plus"></i> Add New Car</button>
                </div>
            </div>

            <div class="table-wrap">
                <table class="responsive-table" id="fleetTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Car ID</th>
                            <th>Model</th>
                            <th>Seats</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                                        
                    <tbody id="fleetTbody">
                        <?php if (empty($fleet)): ?>
                            <tr><td colspan="7" style="text-align:center;color:#666;padding:1rem;">No vehicles yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($fleet as $car): ?>
                                <tr>
                                    <td style="width:120px;">
                                        <?php
                                            $images = [];
                                            if (!empty($car['image1'])) $images[] = $car['image1'];
                                            if (!empty($car['image2'])) $images[] = $car['image2'];
                                            if (!empty($car['image3'])) $images[] = $car['image3'];
                                        ?>
                                        <?php if (!empty($images)): ?>
                                            <div style="display:flex;gap:6px;align-items:center;">
                                                <?php foreach ($images as $img): ?>
                                                    <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:56px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #e6e6e6;">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="muted small">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($car['car_id']) ?></td>
                                    <td><?= htmlspecialchars($car['model']) ?></td>
                                    <td><?= htmlspecialchars($car['seats']) ?></td>
                                    <td><?= htmlspecialchars($car['type']) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($car['details'], 0, 80, '...')) ?></td>
                                    <td class="actions-col">
                                        <button class="btn" onclick="openEdit(<?= intval($car['id']) ?>)">Edit</button>
                                        <form method="post" action="delete_fleet.php" style="display:inline-block;margin-left:.4rem">
                                            <input type="hidden" name="id" value="<?= intval($car['id']) ?>">
                                            <button class="btn danger" type="submit" onclick="return confirm('Delete this vehicle?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add/Edit Modal -->
    <div id="fleetModal" class="modal" aria-hidden="true">
        <div class="modal-backdrop" id="fleetBackdrop"></div>
        <div class="modal-panel" id="fleetPanel">
            <button class="modal-close" id="fleetModalClose">&times;</button>
            <div id="fleetModalContent">
                <h3 id="fleetModalTitle">Add New Vehicle</h3>

                <!-- The form posts to server endpoints when SIMULATE_SERVER = false -->
                <form id="fleetForm" method="post" action="add_fleet.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="car_id_original" id="car_id_original" value="">

                    <div class="form-grid">
                        <label>Car ID (unique)
              <input name="car_id" id="car_id" required placeholder="CAR-0001">
            </label>

                        <label>Model
              <input name="model" id="model" required placeholder="Toyota Prado">
            </label>

                        <label>Make / Year
              <input name="make" id="make" placeholder="Toyota 2022">
            </label>

                        <label>Seats
              <input name="seats" id="seats" type="number" min="1" placeholder="4">
            </label>

                        <label>Type (Sedan, SUV, Van, etc.)
              <input name="type" id="type" placeholder="SUV">
            </label>

                        <label>Plate Number
              <input name="plate" id="plate" placeholder="ABC-123DE">
            </label>

                        <label>Price / Rate
              <input name="rate" id="rate" placeholder="₦ / day or per trip">
            </label>

                        <label>Other Details
              <textarea name="details" id="details" rows="3" placeholder="Additional info about the vehicle"></textarea>
            </label>

                        <label>Image 1 (jpg/png) <input name="image1" id="image1" type="file" accept="image/*"></label>
                        <label>Image 2 (jpg/png) <input name="image2" id="image2" type="file" accept="image/*"></label>
                        <label>Image 3 (jpg/png) <input name="image3" id="image3" type="file" accept="image/*"></label>

                        <div class="image-previews" style="grid-column:1 / -1;">
                            <div class="thumb"><img id="preview1" src="" alt=""></div>
                            <div class="thumb"><img id="preview2" src="" alt=""></div>
                            <div class="thumb"><img id="preview3" src="" alt=""></div>
                        </div>
                    </div>

                    <div style="margin-top:0.8rem; display:flex; gap:.6rem; justify-content:flex-end;">
                        <button type="submit" id="fleetSaveBtn" class="btn primary">Save</button>
                        <button type="button" id="fleetCancelBtn" class="btn">Cancel</button>
                        <button type="reset" class="btn ghost">Reset</button>
                    </div>

                    <p id="fleetFormMsg" class="muted small" style="margin-top:.6rem;"></p>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal" aria-hidden="true">
        <div class="modal-backdrop" id="viewBackdrop"></div>
        <div class="modal-panel" id="viewPanel">
            <button class="modal-close" id="viewClose">&times;</button>
            <div id="viewContent">
                <h3 id="viewTitle">Car Details</h3>
                <div class="gallery" id="viewGallery"></div>
                <div class="detail-grid" id="viewDetails"></div>
                <div style="margin-top:12px;display:flex;justify-content:flex-end;"><button id="viewCloseBtn" class="btn">Close</button></div>
            </div>
        </div>
    </div>

    <!-- <script src="js/fleet.js"></script> -->
         <script>
    (function(){
        const addBtn = document.getElementById('addFleetBtn');
        const modal = document.getElementById('fleetModal');
        const backdrop = document.getElementById('fleetBackdrop');
        const panel = document.getElementById('fleetPanel');
        const closeBtn = document.getElementById('fleetModalClose');
        const form = document.getElementById('fleetForm');
    
        function openModal() {
            modal.setAttribute('aria-hidden', 'false');
            modal.style.display = 'block';
            document.getElementById('fleetModalTitle').textContent = 'Add New Vehicle';
            form.reset();
            document.getElementById('car_id_original').value = '';
            ['preview1','preview2','preview3'].forEach(id => document.getElementById(id).src = '');
        }
    
        function closeModal() {
            modal.setAttribute('aria-hidden', 'true');
            modal.style.display = 'none';
        }
    
        addBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        document.getElementById('fleetCancelBtn')?.addEventListener('click', closeModal);
    
        // image preview
        function previewInput(fileInputId, previewId) {
            const fi = document.getElementById(fileInputId);
            const prev = document.getElementById(previewId);
            fi?.addEventListener('change', function(){
                const f = this.files && this.files[0];
                if (!f) { prev.src = ''; return; }
                const reader = new FileReader();
                reader.onload = e => prev.src = e.target.result;
                reader.readAsDataURL(f);
            });
        }
        previewInput('image1','preview1');
        previewInput('image2','preview2');
        previewInput('image3','preview3');
    
        // Edit: fetch data via a small endpoint or preloaded JS map
        const fleetMap = {};
        <?php foreach ($fleet as $car): ?>
            fleetMap[<?= intval($car['id']) ?>] = <?= json_encode($car) ?>;
        <?php endforeach; ?>
    
        window.openEdit = function(id) {
            const data = fleetMap[id];
            if (!data) return;
            openModal();
            document.getElementById('fleetModalTitle').textContent = 'Edit Vehicle';
            document.getElementById('car_id_original').value = data.car_id;
            document.getElementById('car_id').value = data.car_id;
            document.getElementById('model').value = data.model;
            document.getElementById('make').value = data.make || '';
            document.getElementById('seats').value = data.seats || '';
            document.getElementById('type').value = data.type || '';
            document.getElementById('plate').value = data.plate || '';
            document.getElementById('rate').value = data.rate || '';
            document.getElementById('details').value = data.details || '';
            document.getElementById('preview1').src = data.image1 || '';
            document.getElementById('preview2').src = data.image2 || '';
            document.getElementById('preview3').src = data.image3 || '';
        };
    })();
    </script>
</body>

</html>